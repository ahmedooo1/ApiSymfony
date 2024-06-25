<?php

namespace App\Repository;

use App\Entity\MenuItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuItem>
 */
class MenuItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuItem::class);
    }

    public function getMenuItemsWithCommentCounts()
    {
        $qb = $this->createQueryBuilder('m')
            ->select('m', 'COUNT(c.id) AS commentCount')
            ->leftJoin('m.comments', 'c')
            ->groupBy('m.id');
    
        return $qb->getQuery()->getResult();
    }
    
}
