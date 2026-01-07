<?php

namespace App\Repository;

use App\Entity\AuthUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuthUser>
 *
 * @method AuthUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthUser[]    findAll()
 * @method AuthUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthUser::class);
    }

    public function save(AuthUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AuthUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByEmail(string $email): ?AuthUser
    {
        return $this->createQueryBuilder('a')
            ->where('a.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
