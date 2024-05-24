<?php

namespace App\Controller;

use App\Entity\Dish;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DishController extends AbstractController
{
    #[Route('/api/restaurateur/dishes', name: 'create_dish', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

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

    #[Route('/api/restaurateur/dishes', name: 'get_dishes', methods: ['GET'])]
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

    #[Route('/api/restaurateur/dishes/{id}', name: 'update_dish', methods: ['PUT'])]
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

    #[Route('/api/restaurateur/dishes/{id}', name: 'delete_dish', methods: ['DELETE'])]
    public function delete(Dish $dish, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($dish);
        $entityManager->flush();

        return new JsonResponse('Dish deleted', JsonResponse::HTTP_OK);
    }
}
