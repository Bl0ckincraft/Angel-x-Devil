<?php

namespace App\Controller\Payment;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {

    }

    #[Route('/stripe/session', name: 'app_stripe_webhook_session')]
    public function index(Request $request, LoggerInterface $logger): Response
    {
        $stripeApiKey = $this->getParameter('app.stripe_session_webhook_secret_key');

        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $stripeApiKey
            );
        } catch (SignatureVerificationException $e) {
            return new Response('Invalid signature', 400);
        }

        if ($event->type == Event::CHECKOUT_SESSION_COMPLETED) {
            $session = $event->data->toArray()['object'];

            /** @var Order $order */
            $order = $this->entityManager->getRepository(Order::class)->findOneByStripeSessionId($session['id']);

            if (!$order) {
                return new Response('Could not found order with stripe session id \''.$session['id'].'\'', 400);
            }

            if ($session['payment_status'] != 'paid') {
                return new Response('Order not paid, so just skip event');
            }

            $cart = $order->getUser()->getCart();
            $cart->clear();
            $order->getUser()->setCart($cart);

            $order->setIsPaid(true);

            $this->entityManager->flush();

            return new Response('The order has been set to paid');
        }

        return new Response('Webhook received successfully');
    }
}
