<?php

namespace App\Controller;

use App\Entity\MenuItem;
use App\Repository\MenuItemRepository;
use Cloudinary\Cloudinary;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Psr\Log\LoggerInterface;

class ApiMenuItemController extends AbstractController
{
    private $cloudinary;
    private $logger;

    public function __construct(Cloudinary $cloudinary, LoggerInterface $logger)
    {
        $this->cloudinary = $cloudinary;
        $this->logger = $logger;
    }

    #[Route('/api/menu', name: 'api_menu_get', methods: ['GET'])]
    public function index(MenuItemRepository $menuItemRepository, SerializerInterface $serializer): JsonResponse
    {
        $items = $menuItemRepository->findAll();
        $json = $serializer->serialize($items, 'json', ['groups' => 'menu_item']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
    

    #[Route('/api/menu', name: 'api_menu_add', methods: ['POST'])]
    public function add(Request $request, ValidatorInterface $validator, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $menuItem = new MenuItem();
        $menuItem->setName($request->request->get('name'));
        $menuItem->setDescription($request->request->get('description'));
        $menuItem->setPrice($request->request->get('price'));

        $errors = $validator->validate($menuItem);
        if ($errors->count() > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $image = $request->files->get('imageUrl');
        if ($image) {
            $uploadResult = $this->cloudinary->uploadApi()->upload($image->getPathname());
            $menuItem->setImageUrl($uploadResult['secure_url']);
        }

        $em->persist($menuItem);
        $em->flush();

        return new JsonResponse($serializer->serialize($menuItem, 'json', ['groups' => 'menu_item']), Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/menu/{id}', name: 'api_menu_update', methods: ['PUT'])]
    public function update(int $id, Request $request, MenuItemRepository $menuItemRepository, ValidatorInterface $validator, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $menuItem = $menuItemRepository->find($id);
    
        if (!$menuItem) {
            return new JsonResponse(['message' => 'Menu item not found'], Response::HTTP_NOT_FOUND);
        }
    
        $contentType = $request->headers->get('Content-Type');
        $this->logger->info("Content-Type: $contentType");
    
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $data = $request->request->all();
            $this->logger->info("Form Data: " . json_encode($data));
    
            if (isset($data['name'])) {
                $menuItem->setName($data['name']);
                $this->logger->info("Set name to: " . $data['name']);
            }
            if (isset($data['description'])) {
                $menuItem->setDescription($data['description']);
                $this->logger->info("Set description to: " . $data['description']);
            }
            if (isset($data['price'])) {
                $menuItem->setPrice($data['price']);
                $this->logger->info("Set price to: " . $data['price']);
            }
    
            $image = $request->files->get('imageUrl');
            if ($image) {
                $this->logger->info("Uploading image to Cloudinary");
                $uploadResult = $this->cloudinary->uploadApi()->upload($image->getPathname());
                $menuItem->setImageUrl($uploadResult['secure_url']);
                $this->logger->info("Set imageUrl to: " . $uploadResult['secure_url']);
            } else {
                $this->logger->info("No image uploaded");
            }
        } else {
            // Handle JSON request body
            $data = json_decode($request->getContent(), true);
            $this->logger->info("JSON Data: " . json_encode($data));
    
            if (isset($data['name'])) {
                $menuItem->setName($data['name']);
                $this->logger->info("Set name to: " . $data['name']);
            }
            if (isset($data['description'])) {
                $menuItem->setDescription($data['description']);
                $this->logger->info("Set description to: " . $data['description']);
            }
            if (isset($data['price'])) {
                $menuItem->setPrice($data['price']);
                $this->logger->info("Set price to: " . $data['price']);
            }
            if (isset($data['imageUrl'])) {
                $menuItem->setImageUrl($data['imageUrl']);
                $this->logger->info("Set imageUrl to: " . $data['imageUrl']);
            }
        }
    
        // Additional logging to check if the values are set correctly
        $this->logger->info("Updated MenuItem: " . json_encode([
            'id' => $menuItem->getId(),
            'name' => $menuItem->getName(),
            'description' => $menuItem->getDescription(),
            'price' => $menuItem->getPrice(),
            'imageUrl' => $menuItem->getImageUrl(),
        ]));
    
        $errors = $validator->validate($menuItem);
        if ($errors->count() > 0) {
            $this->logger->error("Validation Errors: " . json_encode($errors));
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }
    
        $em->persist($menuItem);
        $em->flush();
    
        $this->logger->info("MenuItem updated successfully");
        return new JsonResponse($serializer->serialize($menuItem, 'json', ['groups' => 'menu_item']), Response::HTTP_OK, [], true);
    }
    
    #[Route('/api/menu/{id}', name: 'api_menu_delete', methods: ['DELETE'])]
    public function delete(int $id, MenuItemRepository $menuItemRepository, EntityManagerInterface $em): JsonResponse
    {
        $menuItem = $menuItemRepository->find($id);

        if (!$menuItem) {
            return new JsonResponse(['message' => 'Menu item not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($menuItem);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
