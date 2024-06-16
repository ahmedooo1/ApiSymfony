<?php

namespace App\Controller;

use App\Repository\OrderNotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiOrderNotificationController extends AbstractController
{
    #[Route('/api/admin/notifications', name: 'api_admin_notifications', methods: ['GET'])]
    public function getNotifications(Request $request, OrderNotificationRepository $orderNotificationRepository): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);

        $offset = ($page - 1) * $limit;
        $notifications = $orderNotificationRepository->findBy([], ['createdAt' => 'DESC'], $limit, $offset);
        $totalNotifications = $orderNotificationRepository->count([]);

        $data = [];
        foreach ($notifications as $notification) {
            $data[] = [
                'id' => $notification->getId(),
                'user' => [
                    'name' => $notification->getUser()->getName(),
                    'email' => $notification->getUser()->getEmail(),
                ],
                'details' => $notification->getDetails(),
                'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }

        return new JsonResponse([
            'data' => $data,
            'total' => $totalNotifications,
            'page' => $page,
            'limit' => $limit
        ]);
    }
}
