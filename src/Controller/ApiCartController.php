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
            foreach ($cart->getMenuItems() as $menuItem) {
                $cartItems[] = [
                    'id' => $menuItem->getId(),
                    'name' => $menuItem->getName(),
                    'description' => $menuItem->getDescription(),
                    'price' => $menuItem->getPrice(),
                    'image_url' => $menuItem->getImageUrl(),
                    'quantity' => 1 // Assuming quantity is 1 for simplicity
                ];
            }
        }

        return $this->json($cartItems);
    }

    #[Route('/api/carts', name: 'api_carts_add', methods: ['POST'])]
    public function add(Request $request, UserRepository $userRepository, MenuItemRepository $menuItemRepository, EntityManagerInterface $em): JsonResponse
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

        $cart = new Cart();
        $cart->setUser($user);
        $cart->addMenuItem($menuItem);
        $cart->setCreatedAt(new \DateTimeImmutable());
        $cart->setIsPaid(false);

        $em->persist($cart);
        $em->flush();

        return $this->json($cart, JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/cart/item/{itemId}', name: 'api_cart_item_delete', methods: ['DELETE'])]
    public function removeItem(int $itemId, Request $request, CartRepository $cartRepository, MenuItemRepository $menuItemRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'];

        $cart = $cartRepository->findOneBy(['user' => $userId, 'isPaid' => false]);
        if (!$cart) {
            return $this->json(['message' => 'Cart not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $menuItem = $menuItemRepository->find($itemId);
        if (!$menuItem) {
            return $this->json(['message' => 'Menu item not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $cart->removeMenuItem($menuItem);
        $em->persist($cart);
        $em->flush();

        return $this->json(['message' => 'Item removed from cart successfully'], JsonResponse::HTTP_OK);
    }
}
