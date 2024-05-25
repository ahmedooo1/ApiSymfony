<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use GuzzleHttp\Client;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ResetPasswordController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/api/request-reset-password', name: 'api_request_reset_password', methods: ['POST'])]
    public function requestResetPassword(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $email = $request->request->get('email');
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Generate a reset token and save it to the user entity
        $resetToken = bin2hex(random_bytes(32));
        $user->setResetToken($resetToken);
        $entityManager->persist($user);
        $entityManager->flush();

        // Configure Brevo API client
        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $this->getParameter('brevo_api_key'));
        $client = new Client(['verify' => false]); // Disable SSL verification
        $apiInstance = new TransactionalEmailsApi($client, $config);

        // Create the email
        $sendSmtpEmail = new \Brevo\Client\Model\SendSmtpEmail([
            'subject' => 'Password Reset Request',
            'sender' => ['name' => 'THE-CHEF 76', 'email' => 'projetfinal78@gmail.com'],
            'to' => [['email' => $user->getEmail()]],
            'htmlContent' => '
                <div style="font-family: Arial, sans-serif; color: #333;">
                    <h2 style="color: #4CAF50;">Demande de réinitialisation de mot de passe</h2>
                    <p>Bonjour ' . $user->getUsername() . ',</p>
                    <p>Nous avons reçu une demande de réinitialisation de votre mot de passe. Veuillez cliquer sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
                    <p style="text-align: center;">
                        <a href="http://localhost:3000/reset-password/' . $resetToken . '" 
                           style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
                           Réinitialiser le mot de passe
                        </a>
                    </p>
                    <p>Si vous n\'avez pas demandé de réinitialisation de mot de passe, veuillez ignorer cet e-mail ou contacter le support si vous avez des questions.</p>
                    <p>Merci,<br>L\'équipe THE-CHEF 76</p>
                    <p style="text-align: center;">
                        <img src="https://example.com/path/to/logo.png" alt="CHEZ-MOI 76" style="width: 100px; height: auto;">
                    </p>
                </div>'
        ]);

        try {
            $apiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (\Exception $e) {
            // Log the exception message and stack trace
            $this->logger->error('Email sending failed: ' . $e->getMessage());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            return $this->json(['message' => 'Failed to send email', 'error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['message' => 'Password reset email sent']);
    }

    #[Route('/api/reset-password/{token}', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(Request $request, $token, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $this->logger->info('Reset password request received for token: ' . $token);

        $user = $userRepository->findOneBy(['resetToken' => $token]);

        if (!$user) {
            $this->logger->error('Invalid reset token: ' . $token);
            return $this->json(['message' => 'Invalid reset token'], Response::HTTP_BAD_REQUEST);
        }

        $newPassword = $request->request->get('password');
        if (!$newPassword) {
            $this->logger->error('No password provided');
            return $this->json(['message' => 'No password provided'], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $user->setResetToken(null);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->logger->info('Password reset successfully for user: ' . $user->getEmail());

        return $this->json(['message' => 'Password reset successfully']);
    }
}

