<?php

namespace App\Controller;

use App\Entity\MenuItem;
use App\Repository\CategoryRepository;
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
    private $serializer;
    private $em;

    public function __construct(LoggerInterface $logger, Filesystem $filesystem, SerializerInterface $serializer, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
        $this->em = $em;
    }

    #[Route('/api/menu/{id}', name: 'api_menu_show', methods: ['GET'])]
    public function show(int $id, MenuItemRepository $menuItemRepository): JsonResponse
    {
        $menuItem = $menuItemRepository->find($id);
        if (!$menuItem) {
            return new JsonResponse(['message' => 'Menu item not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($menuItem, 'json', ['groups' => 'menu_item']);
        return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/menu', name: 'api_menu_get', methods: ['GET'])]
    public function index(Request $request, MenuItemRepository $menuItemRepository): JsonResponse
    {
        try {
            $categoryId = $request->query->get('categoryId');

            if ($categoryId) {
                $items = $menuItemRepository->createQueryBuilder('m')
                    ->select('m', 'COUNT(c.id) AS commentCount')
                    ->leftJoin('m.comment', 'c')
                    ->where('m.category = :categoryId')
                    ->setParameter('categoryId', $categoryId)
                    ->groupBy('m.id')
                    ->getQuery()
                    ->getResult();
            } else {
                $items = $menuItemRepository->getMenuItemsWithCommentCounts();
            }

            $json = $this->serializer->serialize($items, 'json', ['groups' => 'menu_item']);
            return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch menu items', ['error' => $e->getMessage()]);
            return new JsonResponse(['message' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/menu', name: 'api_menu_add', methods: ['POST'])]
    public function add(Request $request, ValidatorInterface $validator, CategoryRepository $categoryRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['description']) || !isset($data['price']) || !isset($data['category_id'])) {
            return new JsonResponse(['message' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $category = $categoryRepository->find($data['category_id']);
        if (!$category) {
            return new JsonResponse(['message' => 'Category not found'], Response::HTTP_BAD_REQUEST);
        }

        $menuItem = new MenuItem();
        $menuItem->setName($data['name']);
        $menuItem->setDescription($data['description']);
        $menuItem->setPrice($data['price']);
        $menuItem->setCategory($category);

        if (isset($data['image_url'])) {
            $decodedImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['image_url']));
            $imageName = uniqid() . '.jpg';
            file_put_contents($this->getParameter('images_directory') . '/' . $imageName, $decodedImage);
            $menuItem->setImageUrl('/uploads/images/' . $imageName);
        }

        $errors = $validator->validate($menuItem);
        if ($errors->count() > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($menuItem);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($menuItem, 'json', ['groups' => 'menu_item']), Response::HTTP_CREATED, [], true);
    }

    #[Route('/api/menu/{id}', name: 'api_menu_update', methods: ['PUT'])]
    public function update(int $id, Request $request, MenuItemRepository $menuItemRepository, ValidatorInterface $validator, CategoryRepository $categoryRepository): JsonResponse
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
        if (isset($data['category_id'])) {
            $category = $categoryRepository->find($data['category_id']);
            if ($category) {
                $menuItem->setCategory($category);
            }
        }
        if (isset($data['image_url'])) {
            $decodedImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['image_url']));
            $imageName = uniqid() . '.jpg';
            file_put_contents($this->getParameter('images_directory') . '/' . $imageName, $decodedImage);
            $menuItem->setImageUrl('/uploads/images/' . $imageName);
        }

        $errors = $validator->validate($menuItem);
        if ($errors->count() > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->em->persist($menuItem);
        $this->em->flush();

        return new JsonResponse($this->serializer->serialize($menuItem, 'json', ['groups' => 'menu_item']), Response::HTTP_OK, [], true);
    }

    #[Route('/api/menu/{id}', name: 'api_menu_delete', methods: ['DELETE'])]
    public function delete(int $id, MenuItemRepository $menuItemRepository): JsonResponse
    {
        $menuItem = $menuItemRepository->find($id);

        if (!$menuItem) {
            return new JsonResponse(['message' => 'Menu item not found'], Response::HTTP_NOT_FOUND);
        }

        // Suppression des commentaires associés
        $comments = $menuItem->getComments();
        foreach ($comments as $comment) {
            $this->em->remove($comment);
        }

        // Suppression de l'image associée
        if ($menuItem->getImageUrl()) {
            $imagePath = $this->getParameter('kernel.project_dir') . '/public/images' . $menuItem->getImageUrl();
            if ($this->filesystem->exists($imagePath)) {
                $this->filesystem->remove($imagePath);
            }
        }

        $this->em->remove($menuItem);
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
