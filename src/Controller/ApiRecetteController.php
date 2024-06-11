<?php

namespace App\Controller;

use App\Entity\Recette;
use App\Repository\CategoryRepository;
use App\Repository\RecetteRepository;
use App\Service\Search\RecetteSearch;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiRecetteController extends AppController
{
    #[Route('/api/recette', name: 'app_api_recette',methods: ['GET'])]
    public function index(Request $request, RecetteSearch $recetteSearch): JsonResponse
    {
        $recettes = $recetteSearch->request($request);
        $json = $this->serializer->serialize($recettes,'json',['groups'=> 'all_recette']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/recette/{id}', name: 'app_api_recette_one',methods: ['GET'])]
    public function one(int $id, RecetteRepository $recetteRepository): JsonResponse
    {
        $recette = $recetteRepository->find($id);
        if(!$recette) {
            return $this->jsonNotFound('recette');
        }
        $json = $this->serializer->serialize($recette,'json',['groups'=> 'all_recette']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }


    #[Route('/api/recette', name: 'app_api_recette_add',methods: ['POST'])]
    public function add(Request $request,  ValidatorInterface $validator, CategoryRepository $categoryRepository): JsonResponse
    {
        $recette = $this->serializer->deserialize($request->getContent(),Recette::class,'json');
        $errors = $validator->validate($recette);
        if ($errors->count() > 0) {
            return $this->jsonBadRequest($errors);
        }
        // category
        $content = $request->toArray();
        $idcat = $content['category'] ?? -1;
        $category = $categoryRepository->find($idcat);
        if(!empty($category)) {$recette->setCategory($category);}
        // created_at
        $recette->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($recette);
        $this->em->flush();

        $json = $this->serializer->serialize($recette, 'json', ['groups' => 'all_recette']);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }


    #[Route('/api/recette/{id}', name: 'app_api_recette_edit',methods: ['PUT'])]
    public function edit(int $id,RecetteRepository $recetteRepository,Request $request, CategoryRepository $categoryRepository, ValidatorInterface $validator): JsonResponse
    {

        $currentRecette = $recetteRepository->find($id);
        if(!$currentRecette) {
            return $this->jsonNotFound('recette');
        }

        $recette = $this->serializer->deserialize($request->getContent(),Recette::class,'json',[AbstractNormalizer::OBJECT_TO_POPULATE => $currentRecette]);
        $errors = $validator->validate($recette);
        if ($errors->count() > 0) {
            return $this->jsonBadRequest($errors);
        }
        // category
        $content = $request->toArray();
        $idcat = $content['category'] ?? -1;
        $recette->setCategory($categoryRepository->find($idcat));

        $this->em->persist($recette);
        $this->em->flush();

        $json = $this->serializer->serialize($recette, 'json', ['groups' => 'all_recette']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/api/recette/{id}', name: 'app_api_recette_delete', requirements: ['id' => '\d+'],methods: ['DELETE'])]
    public function delete(int $id,  RecetteRepository $recetteRepository): JsonResponse
    {
        $recette = $recetteRepository->find($id);
        if ($recette) {
            $this->em->remove($recette);
            $this->em->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }
        return $this->jsonNotFound('Product');
    }
}
