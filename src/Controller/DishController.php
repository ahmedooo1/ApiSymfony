<?php

namespace App\Controller;

use App\Entity\Dish;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;

class DishController extends AbstractController
{
    #[Route('/api/restaurateur/dishes', name: 'dish_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $dishes = $entityManager->getRepository(Dish::class)->findAll();
        $data = [];

        foreach ($dishes as $dish) {
            $data[] = [
                'id' => $dish->getId(),
                'name' => $dish->getName(),
                'description' => $dish->getDescription(),
                'price' => $dish->getPrice(),
                'type' => $dish->getType(),
                'created_at' => $dish->getCreatedAt()->format('Y-m-d H:i:s'),
                'available_until' => $dish->getAvailableUntil()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/restaurateur/dishes/{id}', name: 'dish_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $dish = $entityManager->getRepository(Dish::class)->find($id);
        if (!$dish) {
            return new JsonResponse(['message' => 'Dish not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $dish->getId(),
            'name' => $dish->getName(),
            'description' => $dish->getDescription(),
            'price' => $dish->getPrice(),
            'type' => $dish->getType(),
            'created_at' => $dish->getCreatedAt()->format('Y-m-d H:i:s'),
            'available_until' => $dish->getAvailableUntil()->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($data);
    }

    #[Route('/api/restaurateur/dishes', name: 'dish_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'], $data['description'], $data['price'], $data['type'], $data['available_until'])) {
            return new JsonResponse(['message' => 'Missing required fields'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $dish = new Dish();
        $dish->setName($data['name']);
        $dish->setDescription($data['description']);
        $dish->setPrice($data['price']);
        $dish->setType($data['type']);
        $dish->setCreatedAt(new \DateTime());
        $dish->setAvailableUntil(new \DateTime($data['available_until']));

        $entityManager->persist($dish);
        $entityManager->flush();

        return new JsonResponse('Dish created', JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/restaurateur/dishes/{id}', name: 'dish_update', methods: ['PUT'])]
    #[IsGranted(['ROLE_ADMIN'])]
    public function update(Request $request, Dish $dish, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $dish->setName($data['name']);
        }

        if (isset($data['description'])) {
            $dish->setDescription($data['description']);
        }

        if (isset($data['price'])) {
            $dish->setPrice($data['price']);
        }

        if (isset($data['type'])) {
            $dish->setType($data['type']);
        }

        if (isset($data['available_until'])) {
            $dish->setAvailableUntil(new \DateTime($data['available_until']));
        }

        $entityManager->flush();

        return new JsonResponse('Dish updated', JsonResponse::HTTP_OK);
    }

    #[Route('/api/restaurateur/dishes/{id}', name: 'dish_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Dish $dish, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$dish) {
            return new JsonResponse(['message' => 'Dish not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($dish);
        $entityManager->flush();

        return new JsonResponse('Dish deleted', JsonResponse::HTTP_OK);
    }
}
