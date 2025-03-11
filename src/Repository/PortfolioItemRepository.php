<?php

namespace App\Repository;

use App\Entity\Portfolio;
use App\Entity\PortfolioItem;
use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PortfolioItem>
 *
 * @method PortfolioItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method PortfolioItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method PortfolioItem[]    findAll()
 * @method PortfolioItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PortfolioItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PortfolioItem::class);
    }

    /**
     * Belirtilen portföydeki öğeleri performanslarına göre sıralar
     * 
     * @return PortfolioItem[]
     */
    public function findByPortfolioOrderedByPerformance(Portfolio $portfolio): array
    {
        return $this->createQueryBuilder('pi')
            ->where('pi.portfolio = :portfolio')
            ->setParameter('portfolio', $portfolio)
            ->orderBy('pi.performancePercent', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Belirtilen portföy ve hisse senedine göre portföy öğesini bulur
     */
    public function findByPortfolioAndStock(Portfolio $portfolio, Stock $stock): ?PortfolioItem
    {
        return $this->createQueryBuilder('pi')
            ->where('pi.portfolio = :portfolio')
            ->andWhere('pi.stock = :stock')
            ->setParameter('portfolio', $portfolio)
            ->setParameter('stock', $stock)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Portföy öğesini kaydeder
     */
    public function save(PortfolioItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Portföy öğesini kaldırır
     */
    public function remove(PortfolioItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 