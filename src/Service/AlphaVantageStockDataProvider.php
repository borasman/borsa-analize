<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AlphaVantageStockDataProvider implements StockDataProviderInterface
{
    private $httpClient;
    private $cache;
    private $logger;
    private $apiKey;
    private $baseUrl = 'https://www.alphavantage.co/query';
    
    public function __construct(
        HttpClientInterface $httpClient,
        CacheInterface $cache,
        LoggerInterface $logger,
        string $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getStockData(string $symbol): ?array
    {
        // Use cache to reduce API calls
        return $this->cache->get(
            sprintf('stock_data_%s', $symbol),
            function (ItemInterface $item) use ($symbol) {
                // Cache for 1 minute during market hours, 15 minutes after hours
                $now = new \DateTime();
                $marketOpen = $this->isMarketOpen($now);
                $item->expiresAfter($marketOpen ? 60 : 900);
                
                try {
                    $response = $this->httpClient->request('GET', $this->baseUrl, [
                        'query' => [
                            'function' => 'GLOBAL_QUOTE',
                            'symbol' => $symbol,
                            'apikey' => $this->apiKey
                        ]
                    ]);
                    
                    $data = $response->toArray();
                    
                    if (isset($data['Global Quote']) && !empty($data['Global Quote'])) {
                        $quote = $data['Global Quote'];
                        
                        return [
                            'symbol' => $quote['01. symbol'],
                            'price' => $quote['05. price'],
                            'change' => $quote['09. change'],
                            'changePercent' => str_replace('%', '', $quote['10. change percent']),
                            'volume' => $quote['06. volume'],
                            'previousClose' => $quote['08. previous close'],
                            'open' => $quote['02. open'],
                            'high' => $quote['03. high'],
                            'low' => $quote['04. low'],
                            'timestamp' => $now->format('Y-m-d H:i:s')
                        ];
                    }
                    
                    $this->logger->warning('Alpha Vantage returned no data for {symbol}', ['symbol' => $symbol]);
                    return null;
                } catch (\Exception $e) {
                    $this->logger->error('Error fetching stock data: {message}', [
                        'message' => $e->getMessage(),
                        'symbol' => $symbol,
                        'exception' => $e
                    ]);
                    
                    return null;
                }
            }
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHistoricalData(string $symbol, \DateTimeInterface $startDate, \DateTimeInterface $endDate, string $interval = '1d'): array
    {
        // Map interval to Alpha Vantage function
        $functionMap = [
            '1d' => 'TIME_SERIES_DAILY',
            '1w' => 'TIME_SERIES_WEEKLY',
            '1m' => 'TIME_SERIES_MONTHLY',
            '1min' => 'TIME_SERIES_INTRADAY',
            '5min' => 'TIME_SERIES_INTRADAY',
            '15min' => 'TIME_SERIES_INTRADAY',
            '30min' => 'TIME_SERIES_INTRADAY',
            '60min' => 'TIME_SERIES_INTRADAY'
        ];
        
        $function = $functionMap[$interval] ?? 'TIME_SERIES_DAILY';
        
        // Format dates
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');
        
        // Use cache
        return $this->cache->get(
            sprintf('historical_data_%s_%s_%s_%s', $symbol, $startDateStr, $endDateStr, $interval),
            function (ItemInterface $item) use ($symbol, $function, $interval, $startDateStr, $endDateStr) {
                // Cache for 24 hours
                $item->expiresAfter(86400);
                
                try {
                    $query = [
                        'function' => $function,
                        'symbol' => $symbol,
                        'apikey' => $this->apiKey,
                        'outputsize' => 'full'
                    ];
                    
                    // Add interval for intraday data
                    if ($function === 'TIME_SERIES_INTRADAY') {
                        $query['interval'] = $interval;
                    }
                    
                    $response = $this->httpClient->request('GET', $this->baseUrl, [
                        'query' => $query
                    ]);
                    
                    $data = $response->toArray();
                    $historicalData = [];
                    
                    // Extract time series data based on function
                    $timeSeriesKey = $this->getTimeSeriesKey($function, $interval);
                    
                    if (isset($data[$timeSeriesKey])) {
                        $timeSeries = $data[$timeSeriesKey];
                        
                        foreach ($timeSeries as $date => $values) {
                            // Skip if outside date range
                            if ($date < $startDateStr || $date > $endDateStr) {
                                continue;
                            }
                            
                            $historicalData[] = [
                                'date' => $date,
                                'open' => $values['1. open'],
                                'high' => $values['2. high'],
                                'low' => $values['3. low'],
                                'close' => $values['4. close'],
                                'volume' => $values['5. volume']
                            ];
                        }
                    }
                    
                    // Sort by date ascending
                    usort($historicalData, function ($a, $b) {
                        return strtotime($a['date']) - strtotime($b['date']);
                    });
                    
                    return $historicalData;
                } catch (\Exception $e) {
                    $this->logger->error('Error fetching historical data: {message}', [
                        'message' => $e->getMessage(),
                        'symbol' => $symbol,
                        'exception' => $e
                    ]);
                    
                    return [];
                }
            }
        );
    }
    
    /**
     * {@inheritdoc}
     */
    public function searchStocks(string $query, int $limit = 10): array
    {
        // Use cache to reduce API calls
        return $this->cache->get(
            sprintf('stock_search_%s_%d', $query, $limit),
            function (ItemInterface $item) use ($query, $limit) {
                // Cache for 1 day
                $item->expiresAfter(86400);
                
                try {
                    $response = $this->httpClient->request('GET', $this->baseUrl, [
                        'query' => [
                            'function' => 'SYMBOL_SEARCH',
                            'keywords' => $query,
                            'apikey' => $this->apiKey
                        ]
                    ]);
                    
                    $data = $response->toArray();
                    $results = [];
                    
                    if (isset($data['bestMatches'])) {
                        $matches = $data['bestMatches'];
                        
                        foreach ($matches as $match) {
                            $results[] = [
                                'symbol' => $match['1. symbol'],
                                'name' => $match['2. name'],
                                'type' => $match['3. type'],
                                'region' => $match['4. region'],
                                'marketClose' => $match['7. timezone'],
                                'currency' => $match['8. currency']
                            ];
                            
                            if (count($results) >= $limit) {
                                break;
                            }
                        }
                    }
                    
                    return $results;
                } catch (\Exception $e) {
                    $this->logger->error('Error searching stocks: {message}', [
                        'message' => $e->getMessage(),
                        'query' => $query,
                        'exception' => $e
                    ]);
                    
                    return [];
                }
            }
        );
    }
    
    /**
     * Check if the market is currently open
     */
    private function isMarketOpen(\DateTimeInterface $now): bool
    {
        // Simplified check for NYSE/NASDAQ market hours
        // Monday-Friday, 9:30 AM to 4:00 PM Eastern Time
        
        // Convert to Eastern Time
        $easternTz = new \DateTimeZone('America/New_York');
        $nowEastern = clone $now;
        $nowEastern->setTimezone($easternTz);
        
        $dayOfWeek = (int) $nowEastern->format('N'); // 1 (Monday) to 7 (Sunday)
        $hour = (int) $nowEastern->format('G');
        $minute = (int) $nowEastern->format('i');
        
        // Weekend check
        if ($dayOfWeek > 5) {
            return false;
        }
        
        // Time check (9:30 AM to 4:00 PM)
        $timeInMinutes = $hour * 60 + $minute;
        $marketOpen = 9 * 60 + 30;  // 9:30 AM
        $marketClose = 16 * 60;     // 4:00 PM
        
        return $timeInMinutes >= $marketOpen && $timeInMinutes < $marketClose;
    }
    
    /**
     * Get the time series key based on function and interval
     */
    private function getTimeSeriesKey(string $function, string $interval): string
    {
        switch ($function) {
            case 'TIME_SERIES_INTRADAY':
                return sprintf('Time Series (%s)', $interval);
            case 'TIME_SERIES_DAILY':
                return 'Time Series (Daily)';
            case 'TIME_SERIES_WEEKLY':
                return 'Weekly Time Series';
            case 'TIME_SERIES_MONTHLY':
                return 'Monthly Time Series';
            default:
                return 'Time Series (Daily)';
        }
    }
} 