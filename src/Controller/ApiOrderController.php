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

        if (!$userId) {
            $this->logger->error('User ID is missing');
            return $this->json(['message' => 'User ID is missing'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->logger->info('Received order creation request', ['userId' => $userId]);

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

        try {
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

            $this->logger->info('Order placed successfully', ['userId' => $userId]);

            return $this->json(['message' => 'Order placed successfully'], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Error placing order', ['exception' => $e->getMessage()]);
            return $this->json(['message' => 'Error placing order'], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function notifyAdminInternal($user, $carts)
    {
        $orderDetails = [];
        $totalPrice = 0;
    
        foreach ($carts as $cart) {
            foreach ($cart->getCartItems() as $cartItem) {
                $menuItem = $cartItem->getMenuItem();
                $quantity = $cartItem->getQuantity();
                $itemTotalPrice = $menuItem->getPrice() * $quantity;
                $orderDetails[] = $menuItem->getName() . ' (Quantité: ' . $quantity . ') - ' . $menuItem->getPrice() . ' € (Total: ' . $itemTotalPrice . ' €)';
                $totalPrice += $itemTotalPrice;
            }
        }
    
        $orderDetailsString = implode(', ', $orderDetails);
    
        // Sauvegarder la notification de commande en base de données
        $this->saveOrderNotification($user, $orderDetailsString, $totalPrice);
    }
    
    private function saveOrderNotification($user, $orderDetails, $totalPrice)
    {
        $this->logger->info('Saving order notification', [
            'user' => $user->getEmail(),
            'details' => $orderDetails,
            'totalPrice' => $totalPrice
        ]);
    
        $notification = new OrderNotification();
        $notification->setUser($user);
        $notification->setDetails($orderDetails . ' | Prix total: ' . $totalPrice . ' €');
    
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
