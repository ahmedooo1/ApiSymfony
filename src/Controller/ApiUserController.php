<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ApiRegisterFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ApiUserController extends AppController
{
    #[Route('/api/user/getcurrent', name: 'app_api_user_by_token', methods: ['POST'])]
    public function ifUserExist(): JsonResponse
    {
        $user = $this->getUser();
        if($user) {
            $json = $this->serializer->serialize($user, 'json', ['groups' => 'user_no_pass']);
            return new JsonResponse($json, Response::HTTP_OK, [], true);
        }
        return $this->json(['message' => 'JWT Token not found'], Response::HTTP_UNAUTHORIZED);
    }


    #[Route('/api/user/register', name: 'app_api_user_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $user = new User();
        $form = $this->createForm(ApiRegisterFormType::class, $user, ['csrf_protection' => false]);
        $formData = json_decode($request->getContent(), true);
        $form->submit($formData);
        if ($form->isValid()) {
            $content = $request->toArray();
            $user->setPassword($hasher->hashPassword($user, $content['plainPassword']));
            $user->addRole('ROLE_USER'); // Ajouter le rôle par défaut
            // Ajouter d'autres rôles si nécessaire
            $this->em->persist($user);
            $this->em->flush();
            return new JsonResponse(['status' => 'success'], Response::HTTP_CREATED);
        } else {
            $errors = $this->getFormErrorsFormType($form);
            return new JsonResponse(['status' => 'failure', 'violations' => $errors], Response::HTTP_FORBIDDEN);
        }

    }

}
