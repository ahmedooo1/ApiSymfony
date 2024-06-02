<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class ContactController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/contact', name: 'contact')]
    public function index(): Response
    {
        return new Response('Contact Us page. Use POST /contact/submit to send a message.');
    }

    #[Route('/contact/submit', name: 'contact_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['email'], $data['message'])) {
            return new Response('Invalid input', Response::HTTP_BAD_REQUEST);
        }

        $name = $data['name'];
        $email = $data['email'];
        $message = $data['message'];

        // Configure Brevo API client
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->getParameter('brevo_api_key'));
        $client = new Client(['verify' => false]); // Disable SSL verification
        $apiInstance = new TransactionalEmailsApi($client, $config);

        // Create the email
        $sendSmtpEmail = new \Brevo\Client\Model\SendSmtpEmail([
            'subject' => 'Contact Us Message',
            'sender' => ['name' => 'THE-CHEF 76', 'email' => 'projetfinal78@gmail.com'], // Use the verified email address
            'replyTo' => ['email' => $email], // Set the reply-to address to the user's email
            'to' => [['email' => 'aahmadooo997@gmail.com']],
            'htmlContent' => '
                <div style="font-family: Arial, sans-serif; color: #333;">
                    <h2 style="color: #4CAF50;">Contact Us Message</h2>
                    <p>Name: ' . $name . '</p>
                    <p>Email: ' . $email . '</p>
                    <p>Message: ' . $message . '</p>
                </div>'
        ]);

        try {
            $this->logger->info('Sending email to: aahmadooo997@gmail.com');
            $apiInstance->sendTransacEmail($sendSmtpEmail);
            $this->logger->info('Email sent successfully');
        } catch (\Exception $e) {
            // Log the exception message and stack trace
            $this->logger->error('Email sending failed: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            return new Response('Failed to send email', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new Response('Message sent successfully', Response::HTTP_OK);
    }
}
