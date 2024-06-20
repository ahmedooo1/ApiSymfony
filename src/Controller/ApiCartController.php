<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\CartRepository;
use App\Repository\MenuItemRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiCartController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/api/cart/empty', name: 'api_cart_empty', methods: ['POST'])]
    public function emptyCart(Request $request, CartRepository $cartRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'];

        $carts = $cartRepository->findBy(['user' => $userId, 'isPaid' => false]);

        foreach ($carts as $cart) {
            $em->remove($cart);
        }

        $em->flush();

        return new JsonResponse(['message' => 'Cart emptied successfully'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/carts', name: 'api_carts_get', methods: ['GET'])]
    public function index(Request $request, CartRepository $cartRepository): JsonResponse
    {
        $userId = $request->query->get('userId');
        $carts = $cartRepository->findBy(['user' => $userId, 'isPaid' => false]);
        
        $cartItems = [];
        foreach ($carts as $cart) {
            foreach ($cart->getCartItems() as $cartItem) {
                $menuItem = $cartItem->getMenuItem();
                $cartItems[] = [
                    'cartId' => $cart->getId(),
                    'menuItemId' => $menuItem->getId(),
                    'name' => $menuItem->getName(),
                    'description' => $menuItem->getDescription(),
                    'price' => $menuItem->getPrice(),
                    'image_url' => $menuItem->getImageUrl(),
                    'quantity' => $cartItem->getQuantity()
                ];
            }
        }

        return $this->json($cartItems);
    }

    #[Route('/api/carts', name: 'api_carts_add', methods: ['POST'])]
    public function add(Request $request, UserRepository $userRepository, MenuItemRepository $menuItemRepository, CartRepository $cartRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'];
        $menuItemId = $data['menuItemId'];
        $quantity = $data['quantity'];

        $user = $userRepository->find($userId);
        $menuItem = $menuItemRepository->find($menuItemId);

        if (!$user || !$menuItem) {
            return $this->json(['message' => 'User or MenuItem not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $existingCart = $cartRepository->findOneBy(['user' => $user, 'isPaid' => false]);
        if (!$existingCart) {
            $cart = new Cart();
            $cart->setUser($user);
            $cart->setCreatedAt(new \DateTimeImmutable());
            $cart->setIsPaid(false);
            $em->persist($cart);
        } else {
            $cart = $existingCart;
        }

        $cartItem = $cart->getCartItems()->filter(function(CartItem $item) use ($menuItem) {
            return $item->getMenuItem() === $menuItem;
        })->first();

        if ($cartItem) {
            $cartItem->setQuantity($cartItem->getQuantity() + $quantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setCart($cart);
            $cartItem->setMenuItem($menuItem);
            $cartItem->setQuantity($quantity);
            $cart->addCartItem($cartItem);
        }

        $em->persist($cart);
        $em->flush();

        return $this->json(['message' => 'Item added to cart successfully'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/cart/item', name: 'api_cart_item_delete', methods: ['DELETE'])]
    public function deleteCartItem(Request $request, CartRepository $cartRepository, MenuItemRepository $menuItemRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'];
        $menuItemId = $data['menuItemId'];
        $cartId = $data['cartId'];

        $this->logger->info('Received delete cart item request', [
            'userId' => $userId,
            'menuItemId' => $menuItemId,
            'cartId' => $cartId
        ]);

        $cart = $cartRepository->find($cartId);

        if (!$cart || $cart->getUser()->getId() !== $userId || $cart->isPaid()) {
            $this->logger->error('Cart not found or access denied', [
                'cartId' => $cartId,
                'userId' => $userId
            ]);
            return $this->json(['message' => 'Cart not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $menuItem = $menuItemRepository->find($menuItemId);

        if (!$menuItem) {
            $this->logger->error('MenuItem not found', [
                'menuItemId' => $menuItemId
            ]);
            return $this->json(['message' => 'MenuItem not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $cartItem = $cart->getCartItems()->filter(function(CartItem $item) use ($menuItem) {
            return $item->getMenuItem() === $menuItem;
        })->first();

        if ($cartItem) {
            $cart->removeCartItem($cartItem);
            $em->remove($cartItem); // Remove the CartItem entity from the database

            if ($cart->getCartItems()->isEmpty()) {
                $em->remove($cart);
            } else {
                $em->persist($cart);
            }
            $em->flush();
            $this->logger->info('Item removed from cart successfully', [
                'menuItemId' => $menuItemId,
                'cartId' => $cartId
            ]);
        } else {
            $this->logger->error('CartItem not found in cart', [
                'menuItemId' => $menuItemId,
                'cartId' => $cartId
            ]);
            return $this->json(['message' => 'CartItem not found in cart'], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json(['message' => 'Item removed from cart successfully'], JsonResponse::HTTP_OK);
    }
}