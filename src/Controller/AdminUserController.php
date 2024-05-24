<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminUserController extends AbstractController
{
    #[Route('/api/admin/users', name: 'admin_get_users', methods: ['GET'])]
    public function index(UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findAll();
        $data = [];

        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/admin/users/{id}', name: 'admin_get_user', methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/api/admin/users', name: 'admin_create_user', methods: ['POST'])]
    public function create(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        if (!isset($data['email'], $data['password'], $data['role'])) {
            return new JsonResponse('Invalid data', JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        $user->setRoles([$data['role']]);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse('User created', JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/admin/users/{id}', name: 'admin_update_user', methods: ['PUT'])]
    public function update(Request $request, User $user, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }

        if (isset($data['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        if (isset($data['role'])) {
            $user->setRoles([$data['role']]);
        }

        $entityManager->flush();

        return new JsonResponse('User updated', JsonResponse::HTTP_OK);
    }

    #[Route('/api/admin/users/{id}', name: 'admin_delete_user', methods: ['DELETE'])]
    public function delete(User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse('User deleted', JsonResponse::HTTP_OK);
    }
}
