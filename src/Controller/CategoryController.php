<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CategoryController extends AbstractController
{
    #[Route('/api/categories', name: 'category_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $data = array_map(function(Category $category) {
            return [
                'id' => $category->getId(),
                'name' => $category->getName(),
            ];
        }, $categories);

        return new JsonResponse($data);
    }

    #[Route('/api/categories/{id}', name: 'category_show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            return new JsonResponse(['message' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $category->getId(),
            'name' => $category->getName(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/api/categories', name: 'category_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $category = new Category();
        $category->setName($data['name']);

        $entityManager->persist($category);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Category created'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/categories/{id}', name: 'category_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            return new JsonResponse(['message' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $category->setName($data['name']);

        $entityManager->persist($category);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Category updated'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/categories/{id}', name: 'category_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);
        if (!$category) {
            return new JsonResponse(['message' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($category);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Category deleted'], JsonResponse::HTTP_OK);
    }
}