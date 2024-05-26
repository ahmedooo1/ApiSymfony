<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Dish;
use App\Repository\DishRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted; // Added this line

class OrderController extends AbstractController
{
    #[Route('/api/orders', name: 'create_order', methods: ['POST'])]
    public function create(Request $request, DishRepository $dishRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $order = new Order();
        $order->setClient($this->getUser());
        $order->setOrderedAt(new \DateTime());

        $entityManager->persist($order);
        $entityManager->flush();

        foreach ($data['items'] as $item) {
            $dish = $dishRepository->find($item['dish_id']);
            if (!$dish) {
                return new JsonResponse("Dish not found: " . $item['dish_id'], JsonResponse::HTTP_NOT_FOUND);
            }

            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setDish($dish);
            $orderItem->setQuantity($item['quantity']);

            $entityManager->persist($orderItem);
        }

        $entityManager->flush();

        return new JsonResponse('Order created', JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/orders', name: 'get_orders', methods: ['GET'])]
    public function getOrders(EntityManagerInterface $entityManager): JsonResponse
    {
        $orders = $entityManager->getRepository(Order::class)->findBy(['client' => $this->getUser()]);
        $data = [];

        foreach ($orders as $order) {
            $items = [];
            foreach ($order->getItems() as $item) {
                $items[] = [
                    'dish_id' => $item->getDish()->getId(),
                    'name' => $item->getDish()->getName(),
                    'quantity' => $item->getQuantity(),
                ];
            }

            $data[] = [
                'id' => $order->getId(),
                'ordered_at' => $order->getOrderedAt()->format('Y-m-d H:i:s'),
                'items' => $items,
            ];
        }

        return new JsonResponse($data);
    }

    #[Route('/api/orders/{id}', name: 'order_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Order $order = null, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$order) {
            return new JsonResponse(['message' => 'Order not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($order);
        $entityManager->flush();

        return new JsonResponse('Order deleted', JsonResponse::HTTP_OK);
    }
}
