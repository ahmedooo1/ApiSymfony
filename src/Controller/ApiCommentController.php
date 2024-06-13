<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\MenuItemRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiCommentController extends AbstractController
{
    #[Route('/api/comments', name: 'api_comments_get', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): JsonResponse
    {
        $comments = $commentRepository->findAll();
        return $this->json($comments);
    }

    #[Route('/api/comments', name: 'api_comments_add', methods: ['POST'])]
    public function add(Request $request, UserRepository $userRepository, MenuItemRepository $menuItemRepository, EntityManagerInterface $em): JsonResponse
    {
        $content = $request->request->get('content');
        $userId = $request->request->get('userId');
        $menuItemId = $request->request->get('menuItemId');

        $user = $userRepository->find($userId);
        $menuItem = $menuItemRepository->find($menuItemId);

        if (!$user || !$menuItem) {
            return $this->json(['message' => 'User or MenuItem not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $comment = new Comment();
        $comment->setContent($content);
        $comment->setUser($user);
        $comment->setMenuItem($menuItem);
        $comment->setCreatedAt(new \DateTimeImmutable());

        $em->persist($comment);
        $em->flush();

        return $this->json($comment, JsonResponse::HTTP_CREATED);
    }
}