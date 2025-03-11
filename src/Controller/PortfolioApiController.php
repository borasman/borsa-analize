<?php

namespace App\Controller;

use App\Entity\Portfolio;
use App\Entity\PortfolioItem;
use App\Entity\Stock;
use App\Repository\PortfolioItemRepository;
use App\Repository\PortfolioRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/portfolios")
 */
class PortfolioApiController extends AbstractController
{
    private $portfolioRepository;
    private $portfolioItemRepository;
    private $stockRepository;
    private $entityManager;
    private $serializer;
    private $validator;
    private $security;

    public function __construct(
        PortfolioRepository $portfolioRepository,
        PortfolioItemRepository $portfolioItemRepository,
        StockRepository $stockRepository,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        Security $security
    ) {
        $this->portfolioRepository = $portfolioRepository;
        $this->portfolioItemRepository = $portfolioItemRepository;
        $this->stockRepository = $stockRepository;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->security = $security;
    }

    /**
     * @Route("", name="api_portfolios_list", methods={"GET"})
     */
    public function list(): JsonResponse
    {
        $user = $this->security->getUser();
        $portfolios = $this->portfolioRepository->findByUser($user);
        
        return $this->json($portfolios, Response::HTTP_OK, [], ['groups' => 'portfolio:read']);
    }

    /**
     * @Route("/{id}", name="api_portfolios_show", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $portfolio = $this->portfolioRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$portfolio) {
            return $this->json(['message' => 'Portfolio not found'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($portfolio, Response::HTTP_OK, [], ['groups' => 'portfolio:read']);
    }

    /**
     * @Route("", name="api_portfolios_create", methods={"POST"})
     */
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name'])) {
            return $this->json(['message' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }
        
        $user = $this->security->getUser();
        
        $portfolio = new Portfolio();
        $portfolio->setName($data['name']);
        $portfolio->setUser($user);
        
        if (isset($data['description'])) {
            $portfolio->setDescription($data['description']);
        }
        
        // Check if this should be the default portfolio
        if (isset($data['is_default']) && $data['is_default']) {
            // Remove default from other portfolios
            $defaultPortfolios = $this->portfolioRepository->findBy([
                'user' => $user,
                'isDefault' => true
            ]);
            
            foreach ($defaultPortfolios as $defaultPortfolio) {
                $defaultPortfolio->setIsDefault(false);
                $this->entityManager->persist($defaultPortfolio);
            }
            
            $portfolio->setIsDefault(true);
        }
        
        // Validate portfolio
        $errors = $this->validator->validate($portfolio);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
            return $this->json(['message' => 'Validation failed', 'errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($portfolio);
        $this->entityManager->flush();
        
        return $this->json(
            $portfolio,
            Response::HTTP_CREATED,
            ['Location' => $this->generateUrl('api_portfolios_show', ['id' => $portfolio->getId()])],
            ['groups' => 'portfolio:read']
        );
    }

    /**
     * @Route("/{id}", name="api_portfolios_update", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $portfolio = $this->portfolioRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$portfolio) {
            return $this->json(['message' => 'Portfolio not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['name'])) {
            $portfolio->setName($data['name']);
        }
        
        if (isset($data['description'])) {
            $portfolio->setDescription($data['description']);
        }
        
        // Check if this should be the default portfolio
        if (isset($data['is_default']) && $data['is_default'] && !$portfolio->isIsDefault()) {
            // Remove default from other portfolios
            $defaultPortfolios = $this->portfolioRepository->findBy([
                'user' => $user,
                'isDefault' => true
            ]);
            
            foreach ($defaultPortfolios as $defaultPortfolio) {
                $defaultPortfolio->setIsDefault(false);
                $this->entityManager->persist($defaultPortfolio);
            }
            
            $portfolio->setIsDefault(true);
        }
        
        // Validate portfolio
        $errors = $this->validator->validate($portfolio);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
            return $this->json(['message' => 'Validation failed', 'errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($portfolio);
        $this->entityManager->flush();
        
        return $this->json($portfolio, Response::HTTP_OK, [], ['groups' => 'portfolio:read']);
    }

    /**
     * @Route("/{id}", name="api_portfolios_delete", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function delete(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $portfolio = $this->portfolioRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$portfolio) {
            return $this->json(['message' => 'Portfolio not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if this is the only portfolio
        $portfolioCount = $this->portfolioRepository->countByUser($user);
        if ($portfolioCount <= 1) {
            return $this->json(['message' => 'Cannot delete the only portfolio'], Response::HTTP_BAD_REQUEST);
        }
        
        // If this is the default portfolio, make another one default
        if ($portfolio->isIsDefault()) {
            $otherPortfolio = $this->portfolioRepository->findOneBy(
                ['user' => $user, 'isDefault' => false],
                ['createdAt' => 'DESC']
            );
            
            if ($otherPortfolio) {
                $otherPortfolio->setIsDefault(true);
                $this->entityManager->persist($otherPortfolio);
            }
        }
        
        $this->entityManager->remove($portfolio);
        $this->entityManager->flush();
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/{id}/items", name="api_portfolio_items_list", methods={"GET"}, requirements={"id"="\d+"})
     */
    public function listItems(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $portfolio = $this->portfolioRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$portfolio) {
            return $this->json(['message' => 'Portfolio not found'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($portfolio->getItems(), Response::HTTP_OK, [], ['groups' => 'portfolio:read']);
    }

    /**
     * @Route("/{id}/items", name="api_portfolio_items_add", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function addItem(Request $request, int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $portfolio = $this->portfolioRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$portfolio) {
            return $this->json(['message' => 'Portfolio not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['symbol']) || !isset($data['quantity']) || !isset($data['average_buy_price'])) {
            return $this->json(['message' => 'Symbol, quantity and average_buy_price are required'], Response::HTTP_BAD_REQUEST);
        }
        
        $stock = $this->stockRepository->findOneBy(['symbol' => $data['symbol']]);
        
        if (!$stock) {
            return $this->json(['message' => 'Stock not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Check if stock already exists in portfolio
        $existingItem = $this->portfolioItemRepository->findOneBy([
            'portfolio' => $portfolio,
            'stock' => $stock
        ]);
        
        if ($existingItem) {
            return $this->json(['message' => 'Stock already exists in portfolio'], Response::HTTP_CONFLICT);
        }
        
        $portfolioItem = new PortfolioItem();
        $portfolioItem->setPortfolio($portfolio);
        $portfolioItem->setStock($stock);
        $portfolioItem->setQuantity((int) $data['quantity']);
        $portfolioItem->setAverageBuyPrice((string) $data['average_buy_price']);
        
        // Calculate total cost and current value
        $totalCost = (float) $data['average_buy_price'] * (int) $data['quantity'];
        $currentValue = (float) $stock->getCurrentPrice() * (int) $data['quantity'];
        
        $portfolioItem->setTotalCost((string) $totalCost);
        $portfolioItem->setCurrentValue((string) $currentValue);
        
        // Calculate performance
        if ($totalCost > 0) {
            $performancePercent = (($currentValue - $totalCost) / $totalCost) * 100;
            $portfolioItem->setPerformancePercent((string) $performancePercent);
        } else {
            $portfolioItem->setPerformancePercent('0');
        }
        
        // Validate portfolio item
        $errors = $this->validator->validate($portfolioItem);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            
            return $this->json(['message' => 'Validation failed', 'errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }
        
        $this->entityManager->persist($portfolioItem);
        
        // Update portfolio total values
        $portfolio->recalculatePortfolioValue();
        $this->entityManager->persist($portfolio);
        
        $this->entityManager->flush();
        
        return $this->json($portfolioItem, Response::HTTP_CREATED, [], ['groups' => 'portfolio:read']);
    }

    /**
     * @Route("/{portfolioId}/items/{itemId}", name="api_portfolio_items_update", methods={"PUT"}, requirements={"portfolioId"="\d+", "itemId"="\d+"})
     */
    public function updateItem(Request $request, int $portfolioId, int $itemId): JsonResponse
    {
        $user = $this->security->getUser();
        $portfolio = $this->portfolioRepository->findOneBy(['id' => $portfolioId, 'user' => $user]);
        
        if (!$portfolio) {
            return $this->json(['message' => 'Portfolio not found'], Response::HTTP_NOT_FOUND);
        }
        
        $portfolioItem = $this->portfolioItemRepository->findOneBy([
            'id' => $itemId,
            'portfolio' => $portfolio
        ]);
        
        if (!$portfolioItem) {
            return $this->json(['message' => 'Portfolio item not found'], Response::HTTP_NOT_FOUND);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (isset($data['quantity'])) {
            $portfolioItem->setQuantity((int) $data['quantity']);
        }
        
        if (isset($data['average_buy_price'])) {
            $portfolioItem->setAverageBuyPrice((string) $data['average_buy_price']);
        }
        
        // Update the portfolio item and portfolio values
        $portfolioItem->updateCurrentValue();
        $portfolio->recalculatePortfolioValue();
        
        $this->entityManager->persist($portfolioItem);
        $this->entityManager->persist($portfolio);
        $this->entityManager->flush();
        
        return $this->json($portfolioItem, Response::HTTP_OK, [], ['groups' => 'portfolio:read']);
    }

    /**
     * @Route("/{portfolioId}/items/{itemId}", name="api_portfolio_items_delete", methods={"DELETE"}, requirements={"portfolioId"="\d+", "itemId"="\d+"})
     */
    public function deleteItem(int $portfolioId, int $itemId): JsonResponse
    {
        $user = $this->security->getUser();
        $portfolio = $this->portfolioRepository->findOneBy(['id' => $portfolioId, 'user' => $user]);
        
        if (!$portfolio) {
            return $this->json(['message' => 'Portfolio not found'], Response::HTTP_NOT_FOUND);
        }
        
        $portfolioItem = $this->portfolioItemRepository->findOneBy([
            'id' => $itemId,
            'portfolio' => $portfolio
        ]);
        
        if (!$portfolioItem) {
            return $this->json(['message' => 'Portfolio item not found'], Response::HTTP_NOT_FOUND);
        }
        
        $this->entityManager->remove($portfolioItem);
        
        // Update portfolio total values
        $portfolio->recalculatePortfolioValue();
        $this->entityManager->persist($portfolio);
        
        $this->entityManager->flush();
        
        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @Route("/{id}/refresh", name="api_portfolio_refresh", methods={"POST"}, requirements={"id"="\d+"})
     */
    public function refreshPortfolio(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        $portfolio = $this->portfolioRepository->findOneBy(['id' => $id, 'user' => $user]);
        
        if (!$portfolio) {
            return $this->json(['message' => 'Portfolio not found'], Response::HTTP_NOT_FOUND);
        }
        
        $items = $portfolio->getItems();
        
        foreach ($items as $item) {
            $item->updateCurrentValue();
            $this->entityManager->persist($item);
        }
        
        $portfolio->recalculatePortfolioValue();
        $this->entityManager->persist($portfolio);
        
        $this->entityManager->flush();
        
        return $this->json($portfolio, Response::HTTP_OK, [], ['groups' => 'portfolio:read']);
    }
} 