<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /** @return Query<int, Comment> */
    public function findParentComments(bool $includeDeleted = false): Query
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.parent IS NULL')
            ->orderBy('c.createdAt', 'DESC');

        if (!$includeDeleted) {
            $qb->andWhere('c.deletedAt IS NULL');
        }

        /** @var Query<int, Comment> $query */
        $query = $qb->getQuery();

        return $query;
    }

    /** @return Query<int, Comment> */
    public function findReplies(Comment $parent, bool $includeDeleted = false): Query
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('c.createdAt', 'DESC');

        if (!$includeDeleted) {
            $qb->andWhere('c.deletedAt IS NULL');
        }

        /** @var Query<int, Comment> $query */
        $query = $qb->getQuery();

        return $query;
    }
}
