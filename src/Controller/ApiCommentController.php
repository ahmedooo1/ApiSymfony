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
        return $this->json($comments, 200, [], ['groups' => 'comment']);
    }

    #[Route('/api/comments', name: 'api_comments_add', methods: ['POST'])]
    public function add(Request $request, UserRepository $userRepository, MenuItemRepository $menuItemRepository, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? null;
        $userId = $data['userId'] ?? null;
        $menuItemId = $data['menuItemId'] ?? null;

        if (!$content || !$userId || !$menuItemId) {
            return $this->json(['message' => 'Missing data'], JsonResponse::HTTP_BAD_REQUEST);
        }

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

        return $this->json($comment, JsonResponse::HTTP_CREATED, [], ['groups' => 'comment']);
    }

    #[Route('/api/comments/{id}', name: 'api_comments_delete', methods: ['DELETE'])]
    public function delete(int $id, CommentRepository $commentRepository, EntityManagerInterface $em): JsonResponse
    {
        $comment = $commentRepository->find($id);

        if (!$comment) {
            return $this->json(['message' => 'Comment not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $em->remove($comment);
        $em->flush();

        return $this->json(['message' => 'Comment deleted'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/comments/{id}', name: 'api_comments_update', methods: ['PUT'])]
    public function update(int $id, Request $request, CommentRepository $commentRepository, EntityManagerInterface $em): JsonResponse
    {
        $comment = $commentRepository->find($id);

        if (!$comment) {
            return $this->json(['message' => 'Comment not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? null;

        if (!$content) {
            return $this->json(['message' => 'Content is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $comment->setContent($content);
        $em->flush();

        return $this->json(['message' => 'Comment updated'], JsonResponse::HTTP_OK);
    }
}
