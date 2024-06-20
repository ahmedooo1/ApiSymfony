<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class PaymentController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/api/payment', name: 'api_payment', methods: ['POST'])]
    public function createPayment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $amount = $data['amount']; // Amount should be in the smallest currency unit (e.g., cents for USD)

        if (!$amount) {
            $this->logger->error('Invalid amount', ['amount' => $amount]);
            return new JsonResponse(['error' => 'Invalid amount'], JsonResponse::HTTP_BAD_REQUEST);
        }

        Stripe::setApiKey('sk_test_51NAd2mCCNM9KNgubwaFR1aunw2HHFCDZqjv8QvdiTFc22hpmuWU4DT5W3AkpkDmzTohXzodGMo6rRUoXqL4KZW2y00zaEvWV0D');

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'eur',
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Stripe PaymentIntent creation failed', ['error' => $e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->logger->info('PaymentIntent created successfully', ['clientSecret' => $paymentIntent->client_secret]);

        return new JsonResponse(['clientSecret' => $paymentIntent->client_secret]);
    }
}
