<?php

namespace App\Command;

use App\Entity\Stock;
use App\Entity\StockHistoricalData;
use App\Repository\StockRepository;
use App\Service\StockDataProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

class UpdateStockPricesCommand extends Command
{
    protected static $defaultName = 'app:update-stock-prices';
    protected static $defaultDescription = 'Update stock prices from the API';

    private $stockRepository;
    private $stockDataProvider;
    private $entityManager;
    private $mercureHub;
    private $serializer;

    public function __construct(
        StockRepository $stockRepository,
        StockDataProviderInterface $stockDataProvider,
        EntityManagerInterface $entityManager,
        HubInterface $mercureHub,
        SerializerInterface $serializer
    ) {
        parent::__construct();
        $this->stockRepository = $stockRepository;
        $this->stockDataProvider = $stockDataProvider;
        $this->entityManager = $entityManager;
        $this->mercureHub = $mercureHub;
        $this->serializer = $serializer;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('symbols', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'List of stock symbols to update')
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Update all stocks')
            ->addOption('batch-size', 'b', InputOption::VALUE_REQUIRED, 'Number of stocks to update in a batch', 10)
            ->addOption('delay', 'd', InputOption::VALUE_REQUIRED, 'Delay between batches in seconds', 1)
            ->addOption('force-historical', 'f', InputOption::VALUE_NONE, 'Force historical data update')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $symbols = $input->getArgument('symbols');
        $all = $input->getOption('all');
        $batchSize = (int) $input->getOption('batch-size');
        $delay = (int) $input->getOption('delay');
        $forceHistorical = $input->getOption('force-historical');
        
        if (empty($symbols) && !$all) {
            $io->error('You must specify at least one symbol or use the --all option');
            return Command::FAILURE;
        }
        
        // Get stocks to update
        if ($all) {
            $stocks = $this->stockRepository->findAll();
        } else {
            $stocks = $this->stockRepository->findBySymbols($symbols);
            
            if (count($stocks) === 0) {
                $io->error('No stocks found with the provided symbols');
                return Command::FAILURE;
            }
        }
        
        $io->title('Updating Stock Prices');
        $io->text(sprintf('Found %d stocks to update', count($stocks)));
        
        $progressBar = new ProgressBar($output, count($stocks));
        $progressBar->start();
        
        $updated = 0;
        $errors = 0;
        $updatedStocks = [];
        
        // Process in batches to respect API rate limits
        $batches = array_chunk($stocks, $batchSize);
        
        foreach ($batches as $batch) {
            foreach ($batch as $stock) {
                try {
                    $stockData = $this->stockDataProvider->getStockData($stock->getSymbol());
                    
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
                        $updated++;
                        
                        // Create historical data point
                        $this->createHistoricalDataPoint($stock, $stockData, $forceHistorical);
                    } else {
                        $errors++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    if ($output->isVerbose()) {
                        $io->error(sprintf('Error updating %s: %s', $stock->getSymbol(), $e->getMessage()));
                    }
                }
                
                $progressBar->advance();
            }
            
            // Flush after each batch
            $this->entityManager->flush();
            
            // Publish updates
            foreach ($updatedStocks as $stock) {
                $this->publishStockUpdate($stock);
            }
            
            // Clear updated stocks for next batch
            $updatedStocks = [];
            
            // Add delay between batches
            if ($delay > 0 && isset($batches[0])) {
                sleep($delay);
            }
        }
        
        $progressBar->finish();
        $io->newLine(2);
        
        $io->success(sprintf(
            'Updated %d stocks successfully. %d errors encountered.',
            $updated,
            $errors
        ));
        
        return Command::SUCCESS;
    }
    
    /**
     * Create or update historical data point for today
     */
    private function createHistoricalDataPoint(Stock $stock, array $stockData, bool $force = false): void
    {
        // Get today's date
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        
        // Check if we already have a data point for today
        $repository = $this->entityManager->getRepository(StockHistoricalData::class);
        $existingDataPoint = $repository->findOneBy([
            'stock' => $stock,
            'date' => $today
        ]);
        
        if (!$existingDataPoint || $force) {
            if ($existingDataPoint) {
                // Update existing data point
                $historicalData = $existingDataPoint;
            } else {
                // Create new data point
                $historicalData = new StockHistoricalData();
                $historicalData->setStock($stock);
                $historicalData->setDate($today);
            }
            
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
        
        // Create the update
        $update = new Update(
            'stock/' . $stock->getSymbol(),
            $data
        );
        
        // Publish the update
        $this->mercureHub->publish($update);
    }
} 