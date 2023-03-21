<?php

namespace App\Controller\Account\Order;

use App\Controller\Base\AbstractAppController;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class OrderValidationController extends AbstractAppController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {

    }

    #[Route('/account/order/success/{stripeSessionId}', name: 'app_order_success')]
    public function success($stripeSessionId): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Order $order */
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);

        if (!$order || $order->getUser()->getId() != $user->getId()  || !$order->isPaid()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('account/order/order_success.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/account/order/abort/{stripeSessionId}', name: 'app_order_abort')]
    public function error($stripeSessionId): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Order $order */
        $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($stripeSessionId);

        if (!$order || $order->getUser()->getId() != $user->getId() || $order->isPaid()) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('account/order/order_abort.html.twig', [
            'order' => $order
        ]);
    }
}
