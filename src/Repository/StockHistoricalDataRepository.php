<?php

namespace App\Repository;

use App\Entity\Stock;
use App\Entity\StockHistoricalData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockHistoricalData>
 *
 * @method StockHistoricalData|null find($id, $lockMode = null, $lockVersion = null)
 * @method StockHistoricalData|null findOneBy(array $criteria, array $orderBy = null)
 * @method StockHistoricalData[]    findAll()
 * @method StockHistoricalData[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockHistoricalDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockHistoricalData::class);
    }

    /**
     * Belirli bir hisse senedi için tarih aralığındaki verileri bulur
     * 
     * @return StockHistoricalData[]
     */
    public function findByStockAndDateRange(
        Stock $stock, 
        \DateTimeInterface $startDate, 
        \DateTimeInterface $endDate, 
        string $interval = '1d'
    ): array {
        return $this->createQueryBuilder('shd')
            ->where('shd.stock = :stock')
            ->andWhere('shd.date >= :startDate')
            ->andWhere('shd.date <= :endDate')
            ->andWhere('shd.interval = :interval')
            ->setParameter('stock', $stock)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('interval', $interval)
            ->orderBy('shd.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Bir hisse senedi için en son veriyi getirir
     */
    public function findLatestForStock(Stock $stock, string $interval = '1d'): ?StockHistoricalData
    {
        return $this->createQueryBuilder('shd')
            ->where('shd.stock = :stock')
            ->andWhere('shd.interval = :interval')
            ->setParameter('stock', $stock)
            ->setParameter('interval', $interval)
            ->orderBy('shd.date', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Bir hisse senedi için son N veriyi getirir
     * 
     * @return StockHistoricalData[]
     */
    public function findLatestDataForStock(Stock $stock, int $limit = 30, string $interval = '1d'): array
    {
        return $this->createQueryBuilder('shd')
            ->where('shd.stock = :stock')
            ->andWhere('shd.interval = :interval')
            ->setParameter('stock', $stock)
            ->setParameter('interval', $interval)
            ->orderBy('shd.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Veriyi kaydeder
     */
    public function save(StockHistoricalData $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Veriyi kaldırır
     */
    public function remove(StockHistoricalData $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 