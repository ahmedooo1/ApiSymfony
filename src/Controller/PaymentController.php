<?php

namespace App\Controller;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PaymentController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/api/payments/stripe', name: 'stripe_payment', methods: ['POST'])]
    public function stripePayment(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $orderId = $data['order_id'];
        $paymentMethodId = $data['payment_method_id'];

        // Récupérer la commande
        $order = $entityManager->getRepository(Order::class)->find($orderId);
        if (!$order) {
            $this->logger->error('Order not found', ['order_id' => $orderId]);
            return new JsonResponse(['message' => 'Order not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Intégrer Stripe pour traiter le paiement
        \Stripe\Stripe::setApiKey($this->getParameter('stripe_secret_key'));
        try {
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $order->getTotalAmount(), // Le montant doit être en centimes
                'currency' => 'eur',
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => 'http://localhost:8000/payment-success', // Remplacer par votre URL de retour
            ]);

            if ($paymentIntent->status === 'succeeded') {
                $order->setIsPaid(true);
                $entityManager->flush();
                $this->logger->info('Payment successful', ['order_id' => $orderId]);
                return new JsonResponse(['message' => 'Payment successful']);
            } else {
                $this->logger->error('Payment failed', ['order_id' => $orderId, 'status' => $paymentIntent->status]);
                return new JsonResponse(['message' => 'Payment failed'], JsonResponse::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            $this->logger->error('Payment error', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/payments/paypal', name: 'paypal_payment', methods: ['POST'])]
    public function paypalPayment(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $orderId = $data['order_id'];
        $paypalOrderId = $data['paypal_order_id'];

        // Récupérer la commande
        $order = $entityManager->getRepository(Order::class)->find($orderId);
        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Intégrer PayPal pour vérifier le paiement
        $clientId = $this->getParameter('paypal_client_id');
        $clientSecret = $this->getParameter('paypal_secret');
        $environment = new SandboxEnvironment($clientId, $clientSecret);
        $client = new PayPalHttpClient($environment);

        try {
            $request = new OrdersGetRequest($paypalOrderId);
            $response = $client->execute($request);

            if ($response->result->status === 'COMPLETED') {
                $order->setIsPaid(true);
                $entityManager->flush();
                return new JsonResponse(['message' => 'Payment successful']);
            } else {
                return new JsonResponse(['message' => 'Payment failed'], JsonResponse::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}