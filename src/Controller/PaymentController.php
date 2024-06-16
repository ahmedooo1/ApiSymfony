<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    #[Route('/api/payment', name: 'api_payment', methods: ['POST'])]
    public function createPayment(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $amount = $data['amount']; // Amount should be in the smallest currency unit (e.g., cents for USD)

        Stripe::setApiKey('sk_test_51NAd2mCCNM9KNgubwaFR1aunw2HHFCDZqjv8QvdiTFc22hpmuWU4DT5W3AkpkDmzTohXzodGMo6rRUoXqL4KZW2y00zaEvWV0D');

        $paymentIntent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'eur',
        ]);

        return new JsonResponse(['clientSecret' => $paymentIntent->client_secret]);
    }
}