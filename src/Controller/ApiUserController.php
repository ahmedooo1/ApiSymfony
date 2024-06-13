<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ApiRegisterFormType;
use App\Form\UserEditFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Cloudinary\Cloudinary;

class ApiUserController extends AbstractController
{
    protected EntityManagerInterface $em;
    private Cloudinary $cloudinary;

    public function __construct(EntityManagerInterface $em, Cloudinary $cloudinary)
    {
        $this->em = $em;
        $this->cloudinary = $cloudinary;
    }

    #[Route('/api/user/getcurrent', name: 'app_api_user_by_token', methods: ['POST'])]
    public function getCurrentUser(): JsonResponse
    {
        $user = $this->getUser();
        if ($user) {
            return $this->json($user, Response::HTTP_OK, [], ['groups' => 'user_no_pass']);
        }
        return $this->json(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
    }

    #[Route('/api/user/register', name: 'app_api_user_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(ApiRegisterFormType::class, $user, ['csrf_protection' => false]);
        $formData = json_decode($request->getContent(), true);
        $form->submit($formData);

        if ($form->isValid()) {
            $user->setPassword($hasher->hashPassword($user, $formData['plainPassword']));
            $user->addRole('ROLE_USER'); // Add default role

            $this->em->persist($user);
            $this->em->flush();
            return new JsonResponse(['status' => 'success'], Response::HTTP_CREATED);
        } else {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return new JsonResponse(['status' => 'failure', 'violations' => $errors], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/user/edit', name: 'api_user_edit', methods: ['POST'])]
    public function edit(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $form = $this->createForm(UserEditFormType::class, $user, ['csrf_protection' => false]);
        $form->submit($data);

        if ($form->isValid()) {
            if (isset($data['plainPassword']) && $data['plainPassword']) {
                $user->setPassword($passwordHasher->hashPassword($user, $data['plainPassword']));
            }

            if (isset($data['picture'])) {
                $user->setPicture($data['picture']);
            }

            $this->em->persist($user);
            $this->em->flush();

            return new JsonResponse(['status' => 'success'], Response::HTTP_OK);
        } else {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            return new JsonResponse(['status' => 'failure', 'violations' => $errors], Response::HTTP_BAD_REQUEST);
        }
    }
    
}
