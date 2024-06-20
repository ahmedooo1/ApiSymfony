<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiCategoryController extends AbstractController
{
    private $serializer;
    private $em;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $em)
    {
        $this->serializer = $serializer;
        $this->em = $em;
    }

    #[Route('/api/categories', name: 'api_categories_get', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): JsonResponse
    {
        try {
            $categories = $categoryRepository->findAll();
            return $this->json($categories, 200, [], ['groups' => 'cat']);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/categories/{id}', name: 'app_api_category_single', methods: ['GET'])]
    public function single(int $id, CategoryRepository $categoryRepository): JsonResponse
    {
        $cat = $categoryRepository->find($id);
        if (!$cat) {
            return new JsonResponse(['message' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $json = $this->serializer->serialize($cat, 'json', ['groups' => 'cat']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/categories', name: 'app_api_category_add', methods: ['POST'])]
    public function add(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $cat = $this->serializer->deserialize($request->getContent(), Category::class, 'json');
        $errors = $validator->validate($cat);
        if ($errors->count() > 0) {
            return new JsonResponse(['errors' => (string) $errors], JsonResponse::HTTP_BAD_REQUEST);
        }
        $this->em->persist($cat);
        $this->em->flush();
        $json = $this->serializer->serialize($cat, 'json', ['groups' => 'cat']);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/categories/{id}', name: 'app_api_category_update', methods: ['PUT'])]
    public function update(int $id, Request $request, CategoryRepository $categoryRepository, ValidatorInterface $validator): JsonResponse
    {
        $cat_current = $categoryRepository->find($id);
        if (!$cat_current) {
            return new JsonResponse(['message' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $cat = $this->serializer->deserialize($request->getContent(), Category::class, 'json', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $cat_current
        ]);
        $errors = $validator->validate($cat);
        if ($errors->count() > 0) {
            return new JsonResponse(['errors' => (string) $errors], JsonResponse::HTTP_BAD_REQUEST);
        }
        $this->em->persist($cat);
        $this->em->flush();
        $json = $this->serializer->serialize($cat, 'json', ['groups' => 'cat']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/categories/{id}', name: 'app_api_category_delete', methods: ['DELETE'])]
    public function delete(int $id, CategoryRepository $categoryRepository): JsonResponse
    {
        $cat = $categoryRepository->find($id);
        if (!$cat) {
            return new JsonResponse(['message' => 'Category not found'], JsonResponse::HTTP_NOT_FOUND);
        }
        $this->em->remove($cat);
        $this->em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
