<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Dish;
use App\Repository\OrderRepository;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class OrderItemController extends AbstractController
{
    #[Route('/api/order/{orderId}/items', name: 'add_order_item', methods: ['POST'])]
    public function addOrderItem($orderId, Request $request, EntityManagerInterface $entityManager, DishRepository $dishRepository, OrderRepository $orderRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dishId = $data['dish_id'];
        $quantity = $data['quantity'];

        $order = $orderRepository->find($orderId);
        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérification des permissions
        if ($this->getUser() !== $order->getClient() && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You do not have permission to add items to this order.');
        }

        $dish = $dishRepository->find($dishId);
        if (!$dish) {
            return new JsonResponse(['message' => 'Dish not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $orderItem = new OrderItem();
        $orderItem->setOrder($order);
        $orderItem->setDish($dish);
        $orderItem->setQuantity($quantity);

        $entityManager->persist($orderItem);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Order item added successfully'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/order/{orderId}/items', name: 'get_order_items', methods: ['GET'])]
    public function getOrderItems($orderId, OrderRepository $orderRepository): JsonResponse
    {
        $order = $orderRepository->find($orderId);
        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        // Vérification des permissions
        if ($this->getUser() !== $order->getClient() && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You do not have permission to view items of this order.');
        }

        $orderItems = $order->getItems();
        $data = [];

        foreach ($orderItems as $orderItem) {
            $data[] = [
                'id' => $orderItem->getId(),
                'dish' => [
                    'id' => $orderItem->getDish()->getId(),
                    'name' => $orderItem->getDish()->getName(),
                    'description' => $orderItem->getDish()->getDescription(),
                    'price' => $orderItem->getDish()->getPrice(),
                ],
                'quantity' => $orderItem->getQuantity(),
            ];
        }

        return new JsonResponse($data);
    }
}
