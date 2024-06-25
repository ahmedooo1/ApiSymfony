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
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiCommentController extends AbstractController
{
    #[Route('/api/comments', name: 'api_comments_get', methods: ['GET'])]
    public function getComments(Request $request, CommentRepository $commentRepository): JsonResponse
    {
        $menuItemId = $request->query->get('menuItemId');
        $comments = $commentRepository->findBy(['menuItem' => $menuItemId], ['createdAt' => 'DESC']);

        return $this->json($comments, JsonResponse::HTTP_OK, [], ['groups' => 'comment']);
    }

    #[Route('/api/comments', name: 'api_comments_add', methods: ['POST'])]
    public function addComment(Request $request, UserRepository $userRepository, MenuItemRepository $menuItemRepository, EntityManagerInterface $em, ValidatorInterface $validator, SerializerInterface $serializer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = $userRepository->find($data['userId']);
        $menuItem = $menuItemRepository->find($data['menuItemId']);

        if (!$user || !$menuItem) {
            return new JsonResponse(['message' => 'User or MenuItem not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setUser($user);
        $comment->setMenuItem($menuItem);
        $comment->setCreatedAt(new \DateTimeImmutable());

        $errors = $validator->validate($comment);
        if (count($errors) > 0) {
            return $this->json($errors, JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($comment);
        $em->flush();

        return new JsonResponse($serializer->serialize($comment, 'json', ['groups' => 'comment']), JsonResponse::HTTP_CREATED, [], true);
    }

    #[Route('/api/comments/{id}', name: 'api_comments_delete', methods: ['DELETE'])]
    public function deleteComment(int $id, CommentRepository $commentRepository, EntityManagerInterface $em): JsonResponse
    {
        $comment = $commentRepository->find($id);

        if (!$comment) {
            return $this->json(['message' => 'Comment not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $em->remove($comment);
        $em->flush();

        return $this->json(['message' => 'Comment deleted successfully'], JsonResponse::HTTP_OK);
    }

    #[Route('/api/comments/{id}', name: 'api_comments_update', methods: ['PUT'])]
    public function updateComment(int $id, Request $request, CommentRepository $commentRepository, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $comment = $commentRepository->find($id);

        if (!$comment) {
            return $this->json(['message' => 'Comment not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $comment->setContent($data['content']);

        $errors = $validator->validate($comment);
        if (count($errors) > 0) {
            return $this->json($errors, JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($comment);
        $em->flush();

        return $this->json(['message' => 'Comment updated successfully'], JsonResponse::HTTP_OK);
    }
}
