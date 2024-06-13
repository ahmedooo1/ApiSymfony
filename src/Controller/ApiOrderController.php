<?php


namespace App\Controller;

use App\Entity\Order;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiOrderController extends AbstractController
{
    #[Route('/api/orders', name: 'api_orders_get', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): JsonResponse
    {
        $orders = $orderRepository->findAll();
        return $this->json($orders);
    }

    #[Route('/api/orders', name: 'api_orders_add', methods: ['POST'])]
    public function add(Request $request, CartRepository $cartRepository, EntityManagerInterface $em): JsonResponse
    {
        $cartId = $request->request->get('cartId');

        $cart = $cartRepository->find($cartId);

        if (!$cart) {
            return $this->json(['message' => 'Cart not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $order = new Order();
        $order->setCart($cart);
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setIsPaid(false);

        $em->persist($order);
        $em->flush();

        return $this->json($order, JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/orders/{id}/pay', name: 'api_orders_pay', methods: ['POST'])]
    public function pay(int $id, OrderRepository $orderRepository, EntityManagerInterface $em): JsonResponse
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            return $this->json(['message' => 'Order not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $order->setIsPaid(true);
        $em->persist($order);
        $em->flush();

        return $this->json(['message' => 'Order paid successfully']);
    }
}