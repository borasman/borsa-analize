<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Entity\StockHistoricalData;
use App\Repository\StockHistoricalDataRepository;
use App\Repository\StockRepository;
use App\Service\StockDataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api/stocks")
 */
class StockApiController extends AbstractController
{
    private $stockRepository;
    private $stockHistoricalDataRepository;
    private $serializer;
    private $entityManager;
    private $mercureHub;

    public function __construct(
        StockRepository $stockRepository,
        StockHistoricalDataRepository $stockHistoricalDataRepository,
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        HubInterface $mercureHub
    ) {
        $this->stockRepository = $stockRepository;
        $this->stockHistoricalDataRepository = $stockHistoricalDataRepository;
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->mercureHub = $mercureHub;
    }

    /**
     * @Route("", name="api_stocks_list", methods={"GET"})
     */
    public function list(Request $request): JsonResponse
    {
        $sector = $request->query->get('sector');
        
        if ($sector) {
            $stocks = $this->stockRepository->findBySector($sector);
        } else {
            $stocks = $this->stockRepository->findAll();
        }
        
        return $this->json($stocks, Response::HTTP_OK, [], ['groups' => 'stock:read']);
    }

    /**
     * @Route("/{symbol}", name="api_stocks_show", methods={"GET"})
     */
    public function show(string $symbol): JsonResponse
    {
        $stock = $this->stockRepository->findOneBy(['symbol' => $symbol]);
        
        if (!$stock) {
            return $this->json(['message' => 'Stock not found'], Response::HTTP_NOT_FOUND);
        }
        
        return $this->json($stock, Response::HTTP_OK, [], ['groups' => 'stock:read']);
    }

    /**
     * @Route("/{symbol}/history", name="api_stocks_history", methods={"GET"})
     */
    public function history(Request $request, string $symbol): JsonResponse
    {
        $stock = $this->stockRepository->findOneBy(['symbol' => $symbol]);
        
        if (!$stock) {
            return $this->json(['message' => 'Stock not found'], Response::HTTP_NOT_FOUND);
        }
        
        // Parse date parameters
        $startDate = $request->query->has('start_date')
            ? new \DateTime($request->query->get('start_date'))
            : new \DateTime('-7 days');
            
        $endDate = $request->query->has('end_date')
            ? new \DateTime($request->query->get('end_date'))
            : new \DateTime();
            
        $interval = $request->query->get('interval', 'daily');
        
        $historicalData = $this->stockHistoricalDataRepository->findByStockAndDateRange(
            $stock,
            $startDate,
            $endDate,
            $interval
        );
        
        return $this->json($historicalData, Response::HTTP_OK, [], ['groups' => 'stock_history:read']);
    }

    /**
     * @Route("/update-prices", name="api_stocks_update_prices", methods={"POST"})
     */
    public function updatePrices(Request $request, StockDataProviderInterface $stockDataProvider): JsonResponse
    {
        // Check for admin role
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $symbols = $request->request->get('symbols', []);
        $updateAll = empty($symbols);
        $updatedStocks = [];
        
        if ($updateAll) {
            $stocks = $this->stockRepository->findAll();
        } else {
            $stocks = $this->stockRepository->findBySymbols($symbols);
        }
        
        foreach ($stocks as $stock) {
            try {
                $stockData = $stockDataProvider->getStockData($stock->getSymbol());
                
                if ($stockData) {
                    // Update stock data
                    $stock->setCurrentPrice((string) $stockData['price']);
                    $stock->setDayChange((string) $stockData['change']);
                    $stock->setDayChangePercent((string) $stockData['changePercent']);
                    $stock->setVolume((string) $stockData['volume']);
                    $stock->setLastUpdated(new \DateTime());
                    
                    // Optional fields if available
                    if (isset($stockData['open'])) {
                        $stock->setOpenPrice((string) $stockData['open']);
                    }
                    if (isset($stockData['high'])) {
                        $stock->setHighPrice((string) $stockData['high']);
                    }
                    if (isset($stockData['low'])) {
                        $stock->setLowPrice((string) $stockData['low']);
                    }
                    if (isset($stockData['previousClose'])) {
                        $stock->setPreviousClose((string) $stockData['previousClose']);
                    }
                    
                    $this->entityManager->persist($stock);
                    $updatedStocks[] = $stock;
                    
                    // Create a new historical data point if needed
                    $this->createHistoricalDataPoint($stock, $stockData);
                }
            } catch (\Exception $e) {
                // Log error and continue with other stocks
                error_log('Error updating stock ' . $stock->getSymbol() . ': ' . $e->getMessage());
            }
        }
        
        $this->entityManager->flush();
        
        // Publish updates to Mercure
        foreach ($updatedStocks as $stock) {
            $this->publishStockUpdate($stock);
        }
        
        return $this->json([
            'message' => 'Stock prices updated successfully',
            'updated_count' => count($updatedStocks)
        ]);
    }

    /**
     * Create a historical data point for a stock
     */
    private function createHistoricalDataPoint(Stock $stock, array $stockData): void
    {
        // Check if we already have a data point for today
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        $existingDataPoint = $this->stockHistoricalDataRepository->findOneBy([
            'stock' => $stock,
            'date' => $today
        ]);
        
        if (!$existingDataPoint) {
            $historicalData = new StockHistoricalData();
            $historicalData->setStock($stock);
            $historicalData->setDate($today);
            $historicalData->setOpenPrice($stockData['open'] ?? $stock->getCurrentPrice());
            $historicalData->setHighPrice($stockData['high'] ?? $stock->getCurrentPrice());
            $historicalData->setLowPrice($stockData['low'] ?? $stock->getCurrentPrice());
            $historicalData->setClosePrice($stock->getCurrentPrice());
            $historicalData->setVolume($stock->getVolume());
            
            $this->entityManager->persist($historicalData);
        } else {
            // Update the high/low prices if needed
            $currentPrice = (float) $stock->getCurrentPrice();
            $highPrice = (float) $existingDataPoint->getHighPrice();
            $lowPrice = (float) $existingDataPoint->getLowPrice();
            
            if ($currentPrice > $highPrice) {
                $existingDataPoint->setHighPrice((string) $currentPrice);
            }
            
            if ($currentPrice < $lowPrice) {
                $existingDataPoint->setLowPrice((string) $currentPrice);
            }
            
            // Update the close price and volume
            $existingDataPoint->setClosePrice($stock->getCurrentPrice());
            $existingDataPoint->setVolume($stock->getVolume());
            
            $this->entityManager->persist($existingDataPoint);
        }
    }

    /**
     * Publish stock update to Mercure
     */
    private function publishStockUpdate(Stock $stock): void
    {
        // Serialize the stock data
        $data = $this->serializer->serialize($stock, 'json', ['groups' => 'stock:read']);
        
        // Create the update for this specific stock
        $update = new Update(
            'stock/' . $stock->getSymbol(),
            $data
        );
        
        // Publish the update
        $this->mercureHub->publish($update);
    }
} 