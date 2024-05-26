<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileController extends AbstractController
{
    #[Route('/api/user/profile', name: 'get_user_profile', methods: ['GET'])]
    public function getProfile(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'name' => $user->getUsername(), // Assuming 'name' refers to 'username'
        ];

        return new JsonResponse($data);
    }

    #[Route('/api/user/profile', name: 'update_user_profile', methods: ['PUT'])]
    public function updateProfile(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['name'])) {
            $user->setUsername($data['name']); // Assuming 'name' refers to 'username'
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Profile updated successfully']);
    }

    #[Route('/api/user/profile/password', name: 'update_user_password', methods: ['PUT'])]
    public function updatePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            return new JsonResponse(['message' => 'Current password and new password are required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$passwordHasher->isPasswordValid($user, $data['current_password'])) {
            return new JsonResponse(['message' => 'Current password is incorrect'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $data['new_password']));

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Password updated successfully']);
    }
}
