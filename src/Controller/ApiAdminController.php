<?php

namespace App\Controller;


use App\Entity\User;
use App\Repository\OrderNotificationRepository;
use App\Repository\UserRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiAdminController extends AbstractController
{
 
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
  
        $this->em = $em;
    }


   


    #[Route('/api/admin/stats', name: 'api_admin_stats', methods: ['GET'])]
    public function getStats(UserRepository $userRepository, OrderRepository $orderRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $userCount = $userRepository->count([]);
        $orderCount = $orderRepository->count([]);
        $paidOrdersCount = $orderRepository->count(['isPaid' => true]);

        return new JsonResponse([
            'userCount' => $userCount,
            'orderCount' => $orderCount,
            'paidOrdersCount' => $paidOrdersCount,
        ]);
    }

    #[Route('/api/admin/users', name: 'api_admin_users', methods: ['GET'])]
    public function getUsers(Request $request, UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $page = $request->query->get('page', 1);
        $limit = $request->query->get('limit', 10);

        $users = $userRepository->findBy([], null, $limit, ($page - 1) * $limit);
        $totalUsers = $userRepository->count([]);

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'name' => $user->getName(),
            ];
        }

        return new JsonResponse([
            'data' => $data,
            'total' => $totalUsers,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    #[Route('/admin/users/{id}/update', name: 'admin_update_user', methods: ['POST'])]
    public function updateUserRoles($id, Request $request): JsonResponse
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json(['message' => 'User not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
            $this->em->flush();
            return $this->json(['message' => 'User roles updated successfully']);
        }

        return $this->json(['message' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
    }
}
