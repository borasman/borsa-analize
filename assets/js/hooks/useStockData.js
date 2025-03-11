import { useState, useEffect, useRef } from 'react';
import axios from 'axios';

/**
 * Custom hook for real-time stock data
 * 
 * @param {string|null} symbol - Stock symbol to fetch (null for all stocks)
 * @param {boolean} enableRealtime - Whether to enable real-time updates
 * @returns {Object} - Stock data state and control functions
 */
const useStockData = (symbol = null, enableRealtime = true) => {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const eventSourceRef = useRef(null);

    // Fetch initial data
    useEffect(() => {
        const fetchStockData = async () => {
            try {
                setLoading(true);
                
                const endpoint = symbol 
                    ? `/api/stocks/${symbol}`
                    : '/api/stocks';
                    
                const response = await axios.get(endpoint);
                setData(response.data);
                setError(null);
            } catch (err) {
                console.error('Error fetching stock data:', err);
                setError('Failed to fetch stock data. Please try again later.');
            } finally {
                setLoading(false);
            }
        };

        fetchStockData();
    }, [symbol]);

    // Set up real-time connection
    useEffect(() => {
        if (!enableRealtime) return;

        const setupRealTimeConnection = () => {
            try {
                // Close any existing connection
                if (eventSourceRef.current) {
                    eventSourceRef.current.close();
                }

                // Create Mercure hub URL
                const url = new URL('/.well-known/mercure', window.location.href);
                
                // Subscribe to the appropriate topic
                const topic = symbol 
                    ? `stock/${symbol}`
                    : 'stocks/all';
                    
                url.searchParams.append('topic', topic);

                // Create EventSource connection
                const eventSource = new EventSource(url);
                
                // Handle incoming messages
                eventSource.onmessage = (event) => {
                    try {
                        const updatedData = JSON.parse(event.data);
                        setData(updatedData);
                    } catch (e) {
                        console.error('Error parsing stock update:', e);
                    }
                };

                // Handle connection errors
                eventSource.onerror = () => {
                    console.error('EventSource connection error');
                    eventSource.close();
                    
                    // Try to reconnect after a delay
                    setTimeout(setupRealTimeConnection, 5000);
                };

                // Store reference to EventSource
                eventSourceRef.current = eventSource;
            } catch (err) {
                console.error('Error setting up real-time connection:', err);
            }
        };

        setupRealTimeConnection();

        // Clean up connection on unmount
        return () => {
            if (eventSourceRef.current) {
                eventSourceRef.current.close();
                eventSourceRef.current = null;
            }
        };
    }, [symbol, enableRealtime]);

    // Manually refresh data
    const refreshData = async () => {
        try {
            setLoading(true);
            
            const endpoint = symbol 
                ? `/api/stocks/${symbol}`
                : '/api/stocks';
                
            const response = await axios.get(endpoint);
            setData(response.data);
            setError(null);
        } catch (err) {
            console.error('Error refreshing stock data:', err);
            setError('Failed to refresh stock data. Please try again later.');
        } finally {
            setLoading(false);
        }
    };

    return {
        data,
        loading,
        error,
        refreshData
    };
};

export default useStockData; 