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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CommentController extends AbstractController
{

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaginatorInterface $paginator,
    ) {}

    #[Route('/', name: 'homepage', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $parentComments = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Comment::class, 'c')
            ->where('c.parent IS NULL')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery();

        $pagination = $this->paginator->paginate(
            $parentComments,
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
            'pagination' => $pagination,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/view', name: 'comment_view', methods: ['GET', 'POST'])]
    public function show(int $id, Request $request): Response
    {
        $parentComment = $this->entityManager->find(Comment::class, $id);

        // 404 if not found OR if it is itself a reply (has a parent)
        if (!$parentComment || $parentComment->getParent() !== null) {
            throw $this->createNotFoundException('Comment not found');
        }

        $commentReplies = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(Comment::class, 'c')
            ->where('c.parent = :parent')
            ->setParameter('parent', $parentComment)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $pagination = $this->paginator->paginate(
            $commentReplies,
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
            'pagination' => $pagination,
            'form' => $form,
        ]);
    }
}
