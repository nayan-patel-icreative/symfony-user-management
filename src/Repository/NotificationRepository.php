<?php

namespace App\Repository;

use App\Entity\AuthUser;
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * @return Notification[] Returns an array of Notification objects for a specific user, ordered by newest first
     */
    public function findByUserOrderedByNewest(AuthUser $user): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return int Returns the count of unread notifications for a specific user
     */
    public function countUnreadByUser(AuthUser $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :user')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('user', $user)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Marks a notification as read if it belongs to the specified user
     */
    public function markAsRead(int $notificationId, AuthUser $user): ?Notification
    {
        $notification = $this->createQueryBuilder('n')
            ->andWhere('n.id = :id')
            ->andWhere('n.user = :user')
            ->setParameter('id', $notificationId)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if ($notification) {
            $notification->setIsRead(true);
            $this->getEntityManager()->persist($notification);
            $this->getEntityManager()->flush();
        }

        return $notification;
    }
}
