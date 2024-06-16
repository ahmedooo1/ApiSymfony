<?php

// src/Controller/ApiMenuItemController.php

namespace App\Controller;

use App\Entity\MenuItem;
use App\Repository\MenuItemRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

class ApiMenuItemController extends AbstractController
{
    private $logger;
    private $filesystem;

    public function __construct(LoggerInterface $logger, Filesystem $filesystem)
    {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
    }
   
    #[Route('/api/menu/{id}', name: 'api_menu_show', methods: ['GET'])]
    public function show(int $id, MenuItemRepository $menuItemRepository, SerializerInterface $serializer): JsonResponse
    {
        $menuItem = $menuItemRepository->find($id);
        if (!$menuItem) {
            return new JsonResponse(['message' => 'Menu item not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $json = $serializer->serialize($menuItem, 'json', ['groups' => 'menu_item']);
        return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
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
        $data = json_decode($request->getContent(), true);
    
        // Vérifiez que les champs requis sont présents
        if (!isset($data['name']) || !isset($data['description']) || !isset($data['price'])) {
            return new JsonResponse(['message' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }
    
        $menuItem = new MenuItem();
        $menuItem->setName($data['name']);
        $menuItem->setDescription($data['description']);
        $menuItem->setPrice($data['price']);
    
        // Ajoutez une vérification pour l'image
if (isset($data['image_url'])) {
    $decodedImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['image_url']));
    $imageName = uniqid() . '.jpg';
    file_put_contents($this->getParameter('images_directory') . '/' . $imageName, $decodedImage);
    $menuItem->setImageUrl('/uploads/images/' . $imageName);
}
    
        $errors = $validator->validate($menuItem);
        if ($errors->count() > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
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

        $data = json_decode($request->getContent(), true);
        if (isset($data['name'])) {
            $menuItem->setName($data['name']);
        }
        if (isset($data['description'])) {
            $menuItem->setDescription($data['description']);
        }
        if (isset($data['price'])) {
            $menuItem->setPrice($data['price']);
        }
        if (isset($data['image_url'])) {
            $decodedImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['image_url']));
            $imageName = uniqid() . '.jpg';
            file_put_contents($this->getParameter('images_directory') . '/' . $imageName, $decodedImage);
            $menuItem->setImageUrl('/uploads/images/' . $imageName);
        }

        $errors = $validator->validate($menuItem);
        if ($errors->count() > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $em->persist($menuItem);
        $em->flush();

        return new JsonResponse($serializer->serialize($menuItem, 'json', ['groups' => 'menu_item']), Response::HTTP_OK, [], true);
    }
    
    #[Route('/api/menu/{id}', name: 'api_menu_delete', methods: ['DELETE'])]
    public function delete(int $id, MenuItemRepository $menuItemRepository, EntityManagerInterface $em): JsonResponse
    {
        $menuItem = $menuItemRepository->find($id);

        if (!$menuItem) {
            return new JsonResponse(['message' => 'Menu item not found'], Response::HTTP_NOT_FOUND);
        }

        // Suppression des commentaires associés
        $comments = $menuItem->getComments();
        foreach ($comments as $comment) {
            $em->remove($comment);
        }

        // Suppression de l'image associée
        if ($menuItem->getImageUrl()) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/images' . $menuItem->getImageUrl();
            if ($this->filesystem->exists($imagePath)) {
                $this->filesystem->remove($imagePath);
            }
        }

        $em->remove($menuItem);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
