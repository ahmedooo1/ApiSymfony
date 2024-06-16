<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderNotification;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class ApiOrderController extends AbstractController
{
    private $logger;
    private $userRepository;
    private $em;

    public function __construct(LoggerInterface $logger, UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    #[Route('/api/orders', name: 'api_orders_add', methods: ['POST'])]
    public function add(Request $request, CartRepository $cartRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'];

        $user = $this->userRepository->find($userId);
        if (!$user) {
            $this->logger->error('User not found', ['userId' => $userId]);
            return $this->json(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $carts = $cartRepository->findBy(['user' => $user, 'isPaid' => false]);
        if (!$carts) {
            $this->logger->error('Cart not found', ['userId' => $userId]);
            return $this->json(['message' => 'Cart not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        foreach ($carts as $cart) {
            $order = new Order();
            $order->setCart($cart);
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setIsPaid(false);

            $this->em->persist($order);
            $cart->setIsPaid(true);
            $this->em->persist($cart);
        }

        $this->em->flush();

        // Envoyer un email à l'administrateur et ajouter une notification
        $this->notifyAdminInternal($user, $carts);

        return $this->json(['message' => 'Order placed successfully'], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/notify-admin', name: 'api_notify_admin', methods: ['POST'])]
    public function notifyAdmin(Request $request, CartRepository $cartRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'];

        $user = $this->userRepository->find($userId);
        if (!$user) {
            $this->logger->error('User not found', ['userId' => $userId]);
            return $this->json(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $carts = $cartRepository->findBy(['user' => $user, 'isPaid' => false]);
        if (!$carts) {
            $this->logger->error('Cart not found', ['userId' => $userId]);
            return $this->json(['message' => 'Cart not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $orderDetails = [];
        $totalPrice = 0;

        foreach ($carts as $cart) {
            foreach ($cart->getMenuItems() as $menuItem) {
                $orderDetails[] = $menuItem->getName() . ' - ' . $menuItem->getPrice() . ' €';
                $totalPrice += $menuItem->getPrice();
            }
        }

        $orderDetailsString = implode(', ', $orderDetails);

        // Sauvegarder la notification de commande en base de données
        $this->saveOrderNotification($user, $orderDetailsString);

        return new JsonResponse(['message' => 'Admin notified successfully'], JsonResponse::HTTP_OK);
    }

    private function notifyAdminInternal($user, $carts)
    {
        $orderDetails = [];
        $totalPrice = 0;

        foreach ($carts as $cart) {
            foreach ($cart->getMenuItems() as $menuItem) {
                $orderDetails[] = $menuItem->getName() . ' - $' . $menuItem->getPrice();
                $totalPrice += $menuItem->getPrice();
            }
        }

        $orderDetailsString = implode(', ', $orderDetails);

        // Sauvegarder la notification de commande en base de données
        $this->saveOrderNotification($user, $orderDetailsString);
    }

    private function saveOrderNotification($user, $orderDetails)
    {
        $this->logger->info('Saving order notification', [
            'user' => $user->getEmail(),
            'details' => $orderDetails
        ]);

        $notification = new OrderNotification();
        $notification->setUser($user);
        $notification->setDetails($orderDetails);

        $this->em->persist($notification);
        $this->em->flush();
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
