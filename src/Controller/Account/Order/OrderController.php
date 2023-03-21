<?php

namespace App\Controller\Account\Order;

use App\Controller\Base\AbstractAppController;
use App\Controller\Base\NotificationType;
use App\Entity\Address;
use App\Entity\Carrier;
use App\Entity\Order;
use App\Entity\OrderData;
use App\Entity\User;
use App\Form\OrderFormType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractAppController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {

    }

    #[Route('/account/order', name: 'app_order')]
    public function index(Request $request): Response
    {
        $this->decodeNotifications($request);

        /** @var User $user */
        $user = $this->getUser();

        $cart = $user->getCart();
        $cart->checkProducts($this->entityManager);
        $user->setCart($cart);

        $this->entityManager->flush();

        if (empty($cart->getContent())) {
            return $this->redirectToRoute('app_cart');
        }

        if ($user->getAddresses()->isEmpty()) {
            $this->addNotification(NotificationType::INFO, 'Vous devez ajouter une adresse avant de commander.');

            return $this->redirectToRoute('app_add_address', [
                'redirect_to' => $this->generateUrl('app_order'),
                'notifications' => $this->encodeNotifications()
            ]);
        }

        $form = $this->createForm(OrderFormType::class, null, [
            'user' => $user,
        ]);

        return $this->render('account/order/order.html.twig', [
            'form' => $form->createView(),
            'notifications' => $this->getNotifications(),
            'cart' => $user->getCart()->withEntityManager($this->entityManager)
        ]);
    }

    #[Route('/account/order/check_out', name: 'app_check_out')]
    public function checkOut(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $cart = $user->getCart();
        $cart->checkProducts($this->entityManager);
        $user->setCart($cart);

        $this->entityManager->flush();

        $form = $this->createForm(OrderFormType::class, null, [
            'user' => $user
        ]);

        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid() || empty($cart->getContent())) {
            return $this->redirectToRoute('app_cart');
        }

        $productsForStripe = [];

        $date = new DateTimeImmutable();
        /** @var Address $address */
        $address = $form->get('address')->getData();
        /** @var Carrier $carrier */
        $carrier = $form->get('carrier')->getData();

        $order = new Order();
        $order->setReference($date->format('dmY').'-'.uniqid());
        $order->setUser($user);
        $order->setCreatedAt($date);
        $order->setDeliveryFirstname($address->getFirstname());
        $order->setDeliveryLastname($address->getLastname());
        $order->setDeliveryAddress($address->getAddress());
        $order->setDeliveryCity($address->getCity());
        $order->setDeliveryPostal($address->getPostal());
        $order->setDeliveryCountry($address->getCountry());
        $order->setDeliveryPhone($address->getPhone());
        $order->setCarrierName($carrier->getName());
        $order->setCarrierPrice($carrier->getPrice());
        $order->setIsPaid(false);

        $this->entityManager->persist($order);

        foreach ($user->getCart()->getCartComplete($this->entityManager) as $element) {
            $orderData = new OrderData();
            $orderData->setAssociatedOrder($order);
            $orderData->setProductId($element['product']->getId());
            $orderData->setProductName($element['product']->getName());
            $orderData->setProductPrice($element['product']->getPrice());
            $orderData->setQuantity($element['quantity']);
            $orderData->setTotalPrice($element['product']->getPrice() * $element['quantity']);

            $productsForStripe[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $element['product']->getPrice(),
                    'product_data' => [
                        'name' => $element['product']->getName(),
                        'images' => [
                            $this->getParameter('app.domain').'/uploads/products/'.$element['product']->getIllustration()
                        ]
                    ]
                ],
                'quantity' => $element['quantity']
            ];

            $this->entityManager->persist($orderData);
        }

        $productsForStripe[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $carrier->getPrice(),
                'product_data' => [
                    'name' => $carrier->getName(),
                    'images' => []
                ]
            ],
            'quantity' => 1
        ];

        Stripe::setApiKey($this->getParameter('app.stripe_secret_key'));

        $checkout_session = Session::create([
            'customer_email' => $user->getEmail(),
            'line_items' => $productsForStripe,
            'mode' => 'payment',
            'success_url' => $this->getParameter('app.domain').str_replace('stripeSessionId', '{CHECKOUT_SESSION_ID}',$this->generateUrl('app_order_success', [
                 'stripeSessionId' => 'stripeSessionId'
            ])),
            'cancel_url' => $this->getParameter('app.domain').str_replace('stripeSessionId', '{CHECKOUT_SESSION_ID}',$this->generateUrl('app_order_abort', [
                 'stripeSessionId' => 'stripeSessionId'
            ])),
        ]);

        $order->setStripeSessionId($checkout_session->id);

        $this->entityManager->flush();

        return $this->render('account/order/check_out.html.twig', [
            'cart' => $user->getCart()->withEntityManager($this->entityManager),
            'address' => $address,
            'carrier' => $carrier,
            'stripe_public_key' => $this->getParameter('app.stripe_public_key'),
            'stripe_checkout_session_id' => $checkout_session->id
        ]);
    }

    #[Route('/account/order/{reference}', name: 'app_order_show')]
    public function showOrder($reference): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Order $order */
        $order = $this->entityManager->getRepository(Order::class)->findOneByReference($reference);

        if (!$order || $order->getUser()->getId() != $user->getId() || !$order->isPaid()) {
            return $this->redirectToRoute('app_account');
        }

        return $this->render('account/order/show.html.twig', [
            'order' => $order
        ]);
    }
}
