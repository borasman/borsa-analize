<?php

namespace App\Service;

interface StockDataProviderInterface
{
    /**
     * Get real-time stock data
     *
     * @param string $symbol Stock symbol
     * @return array|null Array of stock data or null if not found
     */
    public function getStockData(string $symbol): ?array;
    
    /**
     * Get historical stock data
     *
     * @param string $symbol Stock symbol
     * @param \DateTimeInterface $startDate Start date
     * @param \DateTimeInterface $endDate End date
     * @param string $interval Data interval (e.g., '1d', '1h')
     * @return array Array of historical data points
     */
    public function getHistoricalData(string $symbol, \DateTimeInterface $startDate, \DateTimeInterface $endDate, string $interval = '1d'): array;
    
    /**
     * Search for stocks by name or symbol
     *
     * @param string $query Search query
     * @param int $limit Maximum number of results
     * @return array Array of matching stocks
     */
    public function searchStocks(string $query, int $limit = 10): array;
} 