<?php

namespace App\Repository;

use App\Entity\Portfolio;
use App\Entity\Stock;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * Kullanıcının son işlemlerini bulur
     * 
     * @return Transaction[]
     */
    public function findRecentByUser(User $user, int $limit = 20): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.portfolio', 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.transactionDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Belirli bir portföye ait işlemleri bulur
     * 
     * @return Transaction[]
     */
    public function findByPortfolio(Portfolio $portfolio): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.portfolio = :portfolio')
            ->setParameter('portfolio', $portfolio)
            ->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Belirli bir hisse senedine ait işlemleri bulur
     * 
     * @return Transaction[]
     */
    public function findByStock(Stock $stock): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.stock = :stock')
            ->setParameter('stock', $stock)
            ->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tarih aralığındaki işlemleri bulur
     * 
     * @return Transaction[]
     */
    public function findByDateRange(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.portfolio', 'p')
            ->where('p.user = :user')
            ->andWhere('t.transactionDate >= :startDate')
            ->andWhere('t.transactionDate <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * İşlemi kaydeder
     */
    public function save(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * İşlemi kaldırır
     */
    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 