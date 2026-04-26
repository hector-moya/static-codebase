<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaginatorInterface $paginator,
        private readonly CommentRepository $commentRepository,
    ) {}

    #[Route('/', name: 'homepage', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $showDeleted = $request->query->getBoolean('show_deleted', false);

        $pagination = $this->paginator->paginate(
            $this->commentRepository->findParentComments($showDeleted),
            $request->query->getInt('page', 1),
            10
        );

        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Comment added successfully!');

            return $this->redirectToRoute('homepage');
        }

        return $this->render('comment/index.html.twig', [
            'pagination'   => $pagination,
            'form'         => $form,
            'show_deleted' => $showDeleted,
        ]);
    }

    #[Route('/{id}/view', name: 'comment_view', methods: ['GET', 'POST'])]
    public function show(int $id, Request $request): Response
    {
        $parentComment = $this->entityManager->find(Comment::class, $id);

        if (!$parentComment || $parentComment->getParent() !== null) {
            return $this->render('comment/not_found.html.twig', [], new Response('', 404));
        }

        $showDeleted = $request->query->getBoolean('show_deleted', false);

        $pagination = $this->paginator->paginate(
            $this->commentRepository->findReplies($parentComment, $showDeleted),
            $request->query->getInt('page', 1),
            10
        );

        $reply = new Comment();
        $form = $this->createForm(CommentType::class, $reply);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reply->setParent($parentComment);
            $this->entityManager->persist($reply);
            $this->entityManager->flush();

            $this->addFlash('success', 'Reply added successfully!');

            return $this->redirectToRoute('comment_view', ['id' => $id]);
        }

        return $this->render('comment/view.html.twig', [
            'parentComment' => $parentComment,
            'pagination'    => $pagination,
            'form'          => $form,
            'show_deleted'  => $showDeleted,
        ]);
    }

    #[Route('/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $comment = $this->entityManager->find(Comment::class, $id);

        if (!$comment) {
            return $this->render('comment/not_found.html.twig', [], new Response('', 404));
        }

        $now = new \DateTimeImmutable();
        $comment->setDeletedAt($now);

        foreach ($comment->getReplies() as $reply) {
            $reply->setDeletedAt($now);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Comment deleted.');

        if ($comment->getParent() !== null) {
            return $this->redirectToRoute('comment_view', ['id' => $comment->getParent()->getId()]);
        }

        return $this->redirectToRoute('homepage');
    }

    #[Route('/{id}/edit', name: 'comment_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $comment = $this->entityManager->find(Comment::class, $id);

        if (!$comment) {
            return $this->render('comment/not_found.html.twig', [], new Response('', 404));
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Comment updated.');

            if ($comment->getParent() !== null) {
                return $this->redirectToRoute('comment_view', ['id' => $comment->getParent()->getId()]);
            }

            return $this->redirectToRoute('homepage');
        }

        return $this->render('comment/edit.html.twig', [
            'form'    => $form,
            'comment' => $comment,
        ]);
    }
}
