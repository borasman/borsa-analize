<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stock>
 *
 * @method Stock|null find($id, $lockMode = null, $lockVersion = null)
 * @method Stock|null findOneBy(array $criteria, array $orderBy = null)
 * @method Stock[]    findAll()
 * @method Stock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    /**
     * @return Stock[] Aktif olarak izlenen hisse senetlerini bulur
     */
    public function findActiveStocks(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('s.symbol', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Stock[] Belirtilen sembollere göre hisse senetlerini bulur
     */
    public function findBySymbols(array $symbols): array
    {
        if (empty($symbols)) {
            return [];
        }
        
        return $this->createQueryBuilder('s')
            ->where('s.symbol IN (:symbols)')
            ->setParameter('symbols', $symbols)
            ->orderBy('s.symbol', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Stock[] Belirtilen sembole benzer hisse senetlerini arar
     */
    public function searchBySymbolOrName(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.symbol LIKE :query')
            ->orWhere('s.companyName LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit)
            ->orderBy('s.symbol', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Yeni hisse senedi oluşturur veya mevcut olanı günceller
     */
    public function save(Stock $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Hisse senedini kaldırır
     */
    public function remove(Stock $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 