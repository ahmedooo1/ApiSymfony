<?php

namespace App\Repository;

use App\Entity\OrderNotification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderNotification>
 *
 * @method OrderNotification|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderNotification|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderNotification[]    findAll()
 * @method OrderNotification[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderNotification::class);
    }
}
