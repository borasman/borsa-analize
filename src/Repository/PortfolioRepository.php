<?php

namespace App\Repository;

use App\Entity\Portfolio;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Portfolio>
 *
 * @method Portfolio|null find($id, $lockMode = null, $lockVersion = null)
 * @method Portfolio|null findOneBy(array $criteria, array $orderBy = null)
 * @method Portfolio[]    findAll()
 * @method Portfolio[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PortfolioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portfolio::class);
    }

    /**
     * Belirli bir kullanıcının portföylerini bulur
     * 
     * @return Portfolio[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.isDefault', 'DESC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Kullanıcının varsayılan portföyünü bulur
     */
    public function findDefaultForUser(User $user): ?Portfolio
    {
        return $this->createQueryBuilder('p')
            ->where('p.user = :user')
            ->andWhere('p.isDefault = :isDefault')
            ->setParameter('user', $user)
            ->setParameter('isDefault', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Portföyü ve içindeki tüm öğeleri birlikte getirir
     */
    public function findWithItems(int $id): ?Portfolio
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.portfolioItems', 'pi')
            ->leftJoin('pi.stock', 's')
            ->addSelect('pi', 's')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Portföyü kaydeder
     */
    public function save(Portfolio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Portföyü kaldırır
     */
    public function remove(Portfolio $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 