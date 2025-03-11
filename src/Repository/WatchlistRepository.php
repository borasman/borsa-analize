<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Watchlist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Watchlist>
 *
 * @method Watchlist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Watchlist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Watchlist[]    findAll()
 * @method Watchlist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WatchlistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Watchlist::class);
    }

    /**
     * Belirli bir kullanıcının izleme listelerini bulur
     * 
     * @return Watchlist[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Kullanıcının varsayılan izleme listesini bulur
     */
    public function findDefaultForUser(User $user): ?Watchlist
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->andWhere('w.isDefault = :isDefault')
            ->setParameter('user', $user)
            ->setParameter('isDefault', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * İzleme listesini ve içindeki tüm öğeleri birlikte getirir
     */
    public function findWithItems(int $id): ?Watchlist
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.watchlistItems', 'wi')
            ->leftJoin('wi.stock', 's')
            ->addSelect('wi', 's')
            ->where('w.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * İzleme listesini kaydeder
     */
    public function save(Watchlist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * İzleme listesini kaldırır
     */
    public function remove(Watchlist $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 