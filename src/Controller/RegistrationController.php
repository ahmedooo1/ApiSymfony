<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\GoogleAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\UserRepository;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['username'])) {
            return new Response('Invalid data', Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si un utilisateur avec le même e-mail ou nom d'utilisateur existe déjà
        $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new Response('Email already in use', Response::HTTP_CONFLICT);
        }

        $existingUsername = $userRepository->findOneBy(['username' => $data['username']]);
        if ($existingUsername) {
            return new Response('Username already in use', Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setUsername($data['username']);
        $user->setIsOAuthUser(false); // Added this line to set isOAuthUser to false for local users

        $entityManager->persist($user);
        $entityManager->flush();

        return new Response('User registered!', Response::HTTP_CREATED);
    }

    #[Route('/api/google-auth', name: 'api_google_auth', methods: ['GET'])]
    public function googleAuth(GoogleAuthService $googleAuthService): Response
    {
        $authUrl = $googleAuthService->getAuthorizationUrl();
        return $this->redirect($authUrl);
    }

    #[Route('/api/google-callback', name: 'api_google_callback', methods: ['GET'])]
    public function googleCallback(Request $request, GoogleAuthService $googleAuthService, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $code = $request->query->get('code');
        $token = $googleAuthService->getAccessToken($code);
        $googleUser = $googleAuthService->getUserInfo($token);

        $email = $googleUser->getEmail();
        $existingUser = $userRepository->findOneBy(['email' => $email]);

        if (!$existingUser) {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername($googleUser->getName());
            $user->setPassword('');
            $user->setIsOAuthUser(true);

            $entityManager->persist($user);
            $entityManager->flush();
        }

        return new Response('User authenticated with Google!', Response::HTTP_OK);
    }
}
