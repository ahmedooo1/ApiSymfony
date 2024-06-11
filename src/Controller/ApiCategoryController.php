<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use App\Service\Search\CategorySearch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiCategoryController extends AppController
{
    #[Route('/api/category', name: 'app_api_category', methods: ['GET'])]
    public function index(Request $request, CategorySearch $categorySearch): JsonResponse
    {
        $cats = $categorySearch->request($request);
        //$cats = $categoryRepository->findAll();
        $json = $this->serializer->serialize($cats, 'json', ['groups' => 'cat']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/category/{id}', name: 'app_api_category_single', methods: ['GET'])]
    public function single(int $id, CategoryRepository $categoryRepository) :JsonResponse {
        $cat = $categoryRepository->find($id);
        if(!$cat) {
            return $this->jsonNotFound('category');
        }
        $json = $this->serializer->serialize($cat, 'json', ['groups' => 'cat']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/category', name: 'app_api_category_add', methods: ['POST'])]
    public function add(Request $request, ValidatorInterface $validator) : JsonResponse {
        $cat = $this->serializer->deserialize($request->getContent(), Category::class, 'json');
        $errors = $validator->validate($cat);
        if($errors->count() > 0 ) {
            return $this->jsonBadRequest($errors);
        }
        $this->em->persist($cat);
        $this->em->flush();
        $json = $this->serializer->serialize($cat, 'json', ['groups' => 'cat']);
        return new JsonResponse($json,Response::HTTP_CREATED,[], true);
    }

    #[Route('/api/category/{id}', name: 'app_api_category_update', methods: ['PUT'])]
    public function update(int $id, Request $request, CategoryRepository $categoryRepository, ValidatorInterface $validator)
    {
        $cat_current = $categoryRepository->find($id);
        if(!$cat_current) {
            return $this->jsonNotFound('category');
        }

        $cat = $this->serializer->deserialize($request->getContent(), Category::class, 'json',[AbstractNormalizer::OBJECT_TO_POPULATE => $cat_current]);
        $errors = $validator->validate($cat);
        if ($errors->count() > 0) {
            return $this->jsonBadRequest($errors);
        }
        $this->em->persist($cat);
        $this->em->flush();
        $json = $this->serializer->serialize($cat, 'json', ['groups' => 'cat']);
        return new JsonResponse($json,Response::HTTP_OK,[], true);
    }

    #[Route('/api/category/{id}', name: 'app_api_category_delete', methods: ['DELETE'])]
    public function delete(int $id, CategoryRepository $categoryRepository) : JsonResponse {
        $cat = $categoryRepository->find($id);
        if(!$cat) {
            return $this->jsonNotFound('category');
        }
        $this->em->remove($cat);
        $this->em->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
