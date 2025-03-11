<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\PortfolioItem;
use App\Entity\Transaction;
use App\Repository\PortfolioItemRepository;
use App\Repository\PortfolioRepository;
use App\Repository\StockRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/transactions")
 */
class TransactionApiController extends AbstractController
{
    private $transactionRepository;
    private $portfolioRepository;
    private $portfolioItemRepository;
    private $stockRepository;
    private $entityManager;
    private $serializer;
    private $validator;
    private $security;
    private $mercureHub;

    public function __construct(
        TransactionRepository $transactionRepository,
        PortfolioRepository $portfolioRepository,
        PortfolioItemRepository $portfolioItemRepository,
        StockRepository $stockRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        Security $security,
        HubInterface $mercureHub
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->portfolioRepository = $portfolioRepository;
        $this->portfolioItemRepository = $portfolioItemRepository;
        $this->stockRepository = $stockRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->security = $security;
        $this->mercureHub = $mercureHub;
    }

    /**
     * @Route("", name="api_transactions_list", methods={"GET"})
     */
    public function list(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        
        // Get filter parameters
        $portfolioId = $request->query->get('portfolio_id');
        $symbol = $request->query->get('symbol');
        $type = $request->query->get('type');
        $limit = $request->query->get('limit', 20);
        $offset = $request->query->get('offset', 0);
        
        // Get transactions based on filters
        $transactions = $this->transactionRepository->findByFilters(
            $user,
            $portfolioId,
            $symbol,
            $type,
            $limit,
            $offset
        );
        
        return $this->json($transactions, Response::HTTP_OK, [], ['groups' => 'transaction:read']);
    }

    /**
     * @Route("/{id}", name="api_transactions_show", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $transaction = $this->transactionRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$transaction) {
            return $this->json(['message' => 'Transaction not found'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($transaction, Response::HTTP_OK, [], ['groups' => 'transaction:read']);
    }

    /**
     * @Route("", name="api_transactions_create", methods={"POST"})
     */
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->security->getUser();
        
        // Validate required fields
        $requiredFields = ['portfolio_id', 'symbol', 'type', 'quantity', 'price'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return $this->json(['message' => sprintf('%s is required', $field)], Response::HTTP_BAD_REQUEST);
            }
        }
        
        // Validate transaction type
        if (!in_array($data['type'], [Transaction::TYPE_BUY, Transaction::TYPE_SELL])) {
            return $this->json(['message' => 'Invalid transaction type'], Response::HTTP_BAD_REQUEST);
        }
        
        // Get portfolio
        $portfolio = $this->portfolioRepository->findOneBy([
            'id' => $data['portfolio_id'],
            'user' => $user
        ]);
        
        if (!$portfolio) {
            return $this->json(['message' => 'Portfolio not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Get stock
        $stock = $this->stockRepository->findOneBy(['symbol' => $data['symbol']]);
        
        if (!$stock) {
            return $this->json(['message' => 'Stock not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Create transaction
        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setStock($stock);
        $transaction->setPortfolio($portfolio);
        $transaction->setType($data['type']);
        $transaction->setQuantity((int) $data['quantity']);
        $transaction->setPrice((string) $data['price']);
        
        if (isset($data['fee'])) {
            $transaction->setFee((string) $data['fee']);
        }
        
        if (isset($data['notes'])) {
            $transaction->setNotes($data['notes']);
        }
        
        if (isset($data['transaction_date'])) {
            $transaction->setTransactionDate(new \DateTime($data['transaction_date']));
        }
        
        // Validate transaction
        $errors = $this->validator->validate($transaction);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
            return $this->json(['message' => 'Validation failed', 'errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($transaction);
        
        // Update portfolio
        $this->updatePortfolio($portfolio, $transaction);
        
        $this->entityManager->flush();
        
        // Create notification
        $this->createTransactionNotification($transaction);
        
        // Publish real-time update
        $this->publishTransactionUpdate($transaction);
        
        return $this->json(
            $transaction,
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl('api_transactions_show', ['id' => $transaction->getId()])],
            ['groups' => 'transaction:read']
        );
    }

    /**
     * Update portfolio based on transaction
     */
    private function updatePortfolio(Portfolio $portfolio, Transaction $transaction): void
    {
        $stock = $transaction->getStock();
        $quantity = $transaction->getQuantity();
        $price = (float) $transaction->getPrice();
        $type = $transaction->getType();
        
        // Get existing portfolio item or create new one
        $portfolioItem = $this->portfolioItemRepository->findOneBy([
            'portfolio' => $portfolio,
            'stock' => $stock
        ]);
        
        if (!$portfolioItem) {
            // Only create new portfolio item for buy transactions
            if ($type === Transaction::TYPE_BUY) {
                $portfolioItem = new PortfolioItem();
                $portfolioItem->setPortfolio($portfolio);
                $portfolioItem->setStock($stock);
                $portfolioItem->setQuantity($quantity);
                $portfolioItem->setAverageBuyPrice((string) $price);
                
                $portfolioItem->updateCurrentValue();
                
                $this->entityManager->persist($portfolioItem);
            } else {
                throw new \LogicException('Cannot sell stock that is not in portfolio');
            }
        } else {
            $currentQuantity = $portfolioItem->getQuantity();
            $currentAverageBuyPrice = (float) $portfolioItem->getAverageBuyPrice();
            
            if ($type === Transaction::TYPE_BUY) {
                // Update average buy price and quantity
                $newQuantity = $currentQuantity + $quantity;
                $newTotalCost = ($currentQuantity * $currentAverageBuyPrice) + ($quantity * $price);
                $newAverageBuyPrice = $newTotalCost / $newQuantity;
                
                $portfolioItem->setQuantity($newQuantity);
                $portfolioItem->setAverageBuyPrice((string) $newAverageBuyPrice);
            } else {
                // Decrease quantity for sell transactions
                $newQuantity = $currentQuantity - $quantity;
                
                if ($newQuantity < 0) {
                    throw new \LogicException('Cannot sell more shares than available in portfolio');
                } elseif ($newQuantity === 0) {
                    // Remove portfolio item if no shares left
                    $this->entityManager->remove($portfolioItem);
                } else {
                    // Just update quantity, keep average buy price
                    $portfolioItem->setQuantity($newQuantity);
                }
            }
            
            if ($newQuantity > 0) {
                $portfolioItem->updateCurrentValue();
                $this->entityManager->persist($portfolioItem);
            }
        }
        
        // Update portfolio totals
        $portfolio->recalculatePortfolioValue();
        $this->entityManager->persist($portfolio);
        
        // Mark transaction as processed
        $transaction->setIsProcessed(true);
    }

    /**
     * Create notification for transaction
     */
    private function createTransactionNotification(Transaction $transaction): void
    {
        $stock = $transaction->getStock();
        $type = $transaction->getType();
        $quantity = $transaction->getQuantity();
        $price = $transaction->getPrice();
        $totalAmount = $transaction->getTotalAmount();
        
        $title = sprintf(
            '%s %s %d %s for %s',
            $type === Transaction::TYPE_BUY ? 'Bought' : 'Sold',
            $stock->getSymbol(),
            $quantity,
            $quantity === 1 ? 'share' : 'shares',
            $totalAmount
        );
        
        $message = sprintf(
            'You have %s %d %s of %s (%s) at %s per share for a total of %s.',
            $type === Transaction::TYPE_BUY ? 'purchased' : 'sold',
            $quantity,
            $quantity === 1 ? 'share' : 'shares',
            $stock->getName(),
            $stock->getSymbol(),
            $price,
            $totalAmount
        );
        
        $notification = new Notification();
        $notification->setUser($transaction->getUser());
        $notification->setType(Notification::TYPE_TRANSACTION);
        $notification->setTitle($title);
        $notification->setMessage($message);
        $notification->setStock($stock);
        $notification->setData([
            'transaction_id' => $transaction->getId(),
            'type' => $type,
            'symbol' => $stock->getSymbol(),
            'quantity' => $quantity,
            'price' => $price,
            'total_amount' => $totalAmount
        ]);
        
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    /**
     * Publish transaction update to Mercure
     */
    private function publishTransactionUpdate(Transaction $transaction): void
    {
        // Serialize the transaction data
        $data = $this->serializer->serialize($transaction, 'json', ['groups' => 'transaction:read']);
        
        // Create the update for the user's transactions
        $update = new Update(
            sprintf('user/%d/transactions', $transaction->getUser()->getId()),
            $data
        );
        
        // Publish the update
        $this->mercureHub->publish($update);
    }
} 