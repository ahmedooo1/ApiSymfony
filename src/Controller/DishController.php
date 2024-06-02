<?php

namespace App\Controller;

use App\Entity\Dish;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Cloudinary\Cloudinary;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

class DishController extends AbstractController
{
    private $cloudinary;
    private $logger;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $params->get('cloudinary_cloud_name'),
                'api_key' => $params->get('cloudinary_api_key'),
                'api_secret' => $params->get('cloudinary_api_secret'),
            ],
        ]);
        $this->logger = $logger;
    }

    #[Route('/api/restaurateur/dishes', name: 'dish_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $dishes = $entityManager->getRepository(Dish::class)->findAll();
        $data = array_map(function(Dish $dish) {
            return [
                'id' => $dish->getId(),
                'name' => $dish->getName(),
                'description' => $dish->getDescription(),
                'price' => $dish->getPrice(),
                'type' => $dish->getType(),
                'created_at' => $dish->getCreatedAt()->format('Y-m-d H:i:s'),
                'available_until' => $dish->getAvailableUntil()->format('Y-m-d H:i:s'),
                'image' => $dish->getImage(),
            ];
        }, $dishes);

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
            'image' => $dish->getImage(),
        ];

        return new JsonResponse($data);
    }

    #[Route('/api/restaurateur/dishes', name: 'dish_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = $request->request->all();
            $imageFile = $request->files->get('image');

            if (!$imageFile) {
                return new JsonResponse(['error' => 'Image is required'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $dish = new Dish();
            $dish->setName($data['name']);
            $dish->setDescription($data['description']);
            $dish->setPrice($data['price']);
            $dish->setType($data['type']);
            $dish->setCreatedAt(new \DateTime());
            $dish->setAvailableUntil(new \DateTime($data['available_until']));

            try {
                // Set SSL options for the cURL handler
                $curlOptions = [
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                ];

                $uploadResult = $this->cloudinary->uploadApi()->upload($imageFile->getPathname(), [
                    'curl' => $curlOptions,
                ]);

                if (isset($uploadResult['secure_url'])) {
                    $dish->setImage($uploadResult['secure_url']);
                } else {
                    return new JsonResponse(['error' => 'Failed to upload image'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                }
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Error uploading image: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }

            $entityManager->persist($dish);
            $entityManager->flush();

            return new JsonResponse('Dish created', JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/restaurateur/dishes/{id}', name: 'dish_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    public function update(Request $request, int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $dish = $entityManager->getRepository(Dish::class)->find($id);
        if (!$dish) {
            return new JsonResponse(['message' => 'Dish not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = $request->request->all();
        $this->logger->info('Received data for update', $data);
        $imageFile = $request->files->get('image');

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

        if ($imageFile) {
            try {
                // Set SSL options for the cURL handler
                $curlOptions = [
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_SSL_VERIFYPEER => 0,
                ];

                $uploadResult = $this->cloudinary->uploadApi()->upload($imageFile->getPathname(), [
                    'curl' => $curlOptions,
                ]);

                if (isset($uploadResult['secure_url'])) {
                    $dish->setImage($uploadResult['secure_url']);
                } else {
                    return new JsonResponse(['error' => 'Failed to upload image'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
                }
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Error uploading image: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $entityManager->persist($dish);
        $entityManager->flush();
        $this->logger->info('Dish updated successfully', ['id' => $id, 'data' => $data]);

        return new JsonResponse('Dish updated', JsonResponse::HTTP_OK);
    }

    #[Route('/api/restaurateur/dishes/{id}', name: 'dish_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $dish = $entityManager->getRepository(Dish::class)->find($id);
        if (!$dish) {
            return new JsonResponse(['message' => 'Dish not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($dish);
        $entityManager->flush();

        return new JsonResponse('Dish deleted', JsonResponse::HTTP_OK);
    }
}
