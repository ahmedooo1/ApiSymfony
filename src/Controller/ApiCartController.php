<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Repository\CartRepository;
use App\Repository\MenuItemRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiCartController extends AbstractController
{
    #[Route('/api/carts', name: 'api_carts_get', methods: ['GET'])]
    public function index(CartRepository $cartRepository): JsonResponse
    {
        $carts = $cartRepository->findAll();
        return $this->json($carts);
    }

    #[Route('/api/carts', name: 'api_carts_add', methods: ['POST'])]
    public function add(Request $request, UserRepository $userRepository, MenuItemRepository $menuItemRepository, EntityManagerInterface $em): JsonResponse
    {
        $userId = $request->request->get('userId');
        $menuItemId = $request->request->get('menuItemId');
        $quantity = $request->request->get('quantity');

        $user = $userRepository->find($userId);
        $menuItem = $menuItemRepository->find($menuItemId);

        if (!$user || !$menuItem) {
            return $this->json(['message' => 'User or MenuItem not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $cart = new Cart();
        $cart->setUser($user);
        $cart->addMenuItem($menuItem);
        $cart->setCreatedAt(new \DateTimeImmutable());
        $cart->setIsPaid(false);

        $em->persist($cart);
        $em->flush();

        return $this->json($cart, JsonResponse::HTTP_CREATED);
    }
}