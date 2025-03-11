<?php

namespace App\Repository;

use App\Entity\Stock;
use App\Entity\Watchlist;
use App\Entity\WatchlistItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WatchlistItem>
 *
 * @method WatchlistItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method WatchlistItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method WatchlistItem[]    findAll()
 * @method WatchlistItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WatchlistItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WatchlistItem::class);
    }

    /**
     * Belirtilen izleme listesindeki öğeleri getirir
     * 
     * @return WatchlistItem[]
     */
    public function findByWatchlist(Watchlist $watchlist): array
    {
        return $this->createQueryBuilder('wi')
            ->where('wi.watchlist = :watchlist')
            ->setParameter('watchlist', $watchlist)
            ->orderBy('wi.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Belirli bir izleme listesi ve hisse senedi için izleme listesi öğesini bulur
     */
    public function findByWatchlistAndStock(Watchlist $watchlist, Stock $stock): ?WatchlistItem
    {
        return $this->createQueryBuilder('wi')
            ->where('wi.watchlist = :watchlist')
            ->andWhere('wi.stock = :stock')
            ->setParameter('watchlist', $watchlist)
            ->setParameter('stock', $stock)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * İzleme listesi öğesini kaydeder
     */
    public function save(WatchlistItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * İzleme listesi öğesini kaldırır
     */
    public function remove(WatchlistItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 