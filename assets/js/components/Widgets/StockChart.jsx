import React, { useEffect, useRef, useState, useContext } from 'react';
import { createChart } from 'lightweight-charts';
import axios from 'axios';
import { ThemeContext } from '../../contexts/ThemeContext';
import useStockData from '../../hooks/useStockData';

const StockChart = ({ symbol = 'BIST100' }) => {
    const { theme, isDark } = useContext(ThemeContext);
    const chartContainerRef = useRef(null);
    const chartRef = useRef(null);
    const resizeObserverRef = useRef(null);
    const [timeframe, setTimeframe] = useState('1D');
    const { data: realtimeData, loading, error } = useStockData(symbol, true);

    // Fetch historical data based on symbol and timeframe
    const fetchHistoricalData = async () => {
        try {
            // Map timeframes to date ranges
            const timeframeMap = {
                '1D': { start: '-1 day', interval: '1m' },
                '1W': { start: '-1 week', interval: '15m' },
                '1M': { start: '-1 month', interval: '1h' },
                '3M': { start: '-3 months', interval: '1d' },
                '1Y': { start: '-1 year', interval: '1d' },
                '5Y': { start: '-5 years', interval: '1w' },
            };
            
            const { start } = timeframeMap[timeframe] || timeframeMap['1D'];
            
            // Calculate date parameters
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - Number.parseInt(start, 10));
            
            // Format dates for API
            const formattedStartDate = startDate.toISOString().split('T')[0];
            const formattedEndDate = new Date().toISOString().split('T')[0];
            
            const response = await axios.get(`/api/stocks/${symbol}/history`, {
                params: {
                    start_date: formattedStartDate,
                    end_date: formattedEndDate
                }
            });
            
            // Format data for chart
            return response.data.map(item => ({
                time: new Date(item.timestamp).getTime() / 1000,
                open: Number.parseFloat(item.open || item.price),
                high: Number.parseFloat(item.high || item.price),
                low: Number.parseFloat(item.low || item.price),
                close: Number.parseFloat(item.close || item.price),
                value: Number.parseFloat(item.price),
                volume: Number.parseFloat(item.volume)
            }));
        } catch (err) {
            console.error('Error fetching historical data:', err);
            return [];
        }
    };

    // Initialize and setup chart
    useEffect(() => {
        const initChart = async () => {
            if (!chartContainerRef.current) return;
            
            // Create chart instance
            const chartOptions = {
                layout: {
                    background: { color: theme.colors.chart.background },
                    textColor: theme.colors.chart.text,
                },
                grid: {
                    vertLines: { color: theme.colors.chart.grid },
                    horzLines: { color: theme.colors.chart.grid },
                },
                width: chartContainerRef.current.clientWidth,
                height: 300,
                timeScale: {
                    timeVisible: true,
                    secondsVisible: false,
                },
            };
            
            // Create new chart
            chartRef.current = createChart(chartContainerRef.current, chartOptions);
            
            // Add candlestick series
            const candlestickSeries = chartRef.current.addCandlestickSeries({
                upColor: theme.colors.chart.up,
                downColor: theme.colors.chart.down,
                borderVisible: false,
                wickUpColor: theme.colors.chart.up,
                wickDownColor: theme.colors.chart.down,
            });
            
            // Add volume series
            const volumeSeries = chartRef.current.addHistogramSeries({
                color: theme.colors.chart.volume,
                priceFormat: {
                    type: 'volume',
                },
                priceScaleId: '',
                scaleMargins: {
                    top: 0.8,
                    bottom: 0,
                },
            });
            
            // Fetch and set data
            const historicalData = await fetchHistoricalData();
            if (historicalData.length > 0) {
                candlestickSeries.setData(historicalData);
                
                // Set volume data
                volumeSeries.setData(
                    historicalData.map(item => ({
                        time: item.time,
                        value: item.volume,
                        color: item.close > item.open ? theme.colors.chart.up : theme.colors.chart.down,
                    }))
                );
                
                // Fit content
                chartRef.current.timeScale().fitContent();
            }
            
            // Handle window resize
            const handleResize = () => {
                if (chartRef.current && chartContainerRef.current) {
                    chartRef.current.applyOptions({ 
                        width: chartContainerRef.current.clientWidth 
                    });
                }
            };
            
            // Create resize observer
            resizeObserverRef.current = new ResizeObserver(handleResize);
            resizeObserverRef.current.observe(chartContainerRef.current);
        };
        
        // Initialize chart
        initChart();
        
        // Cleanup
        return () => {
            if (chartRef.current) {
                chartRef.current.remove();
                chartRef.current = null;
            }
            
            if (resizeObserverRef.current && chartContainerRef.current) {
                resizeObserverRef.current.unobserve(chartContainerRef.current);
                resizeObserverRef.current = null;
            }
        };
    }, [symbol, timeframe, theme, fetchHistoricalData]);
    
    // Update last candle with real-time data
    useEffect(() => {
        if (realtimeData && chartRef.current) {
            // Get candlestick series
            const series = chartRef.current.getSeries();
            if (series.length > 0) {
                const candlestickSeries = series[0];
                const volumeSeries = series[1];
                
                // Get last data point
                const lastData = {
                    time: Math.floor(new Date(realtimeData.timestamp).getTime() / 1000),
                    open: Number.parseFloat(realtimeData.open || realtimeData.price),
                    high: Number.parseFloat(realtimeData.high || realtimeData.price),
                    low: Number.parseFloat(realtimeData.low || realtimeData.price),
                    close: Number.parseFloat(realtimeData.price),
                    volume: Number.parseFloat(realtimeData.volume)
                };
                
                // Update last candle
                candlestickSeries.update(lastData);
                
                // Update volume
                volumeSeries.update({
                    time: lastData.time,
                    value: lastData.volume,
                    color: lastData.close > lastData.open ? theme.colors.chart.up : theme.colors.chart.down,
                });
            }
        }
    }, [realtimeData, theme]);
    
    // Timeframe buttons
    const timeframeButtons = ['1D', '1W', '1M', '3M', '1Y', '5Y'];
    
    return (
        <div className="stock-chart-widget">
            {error ? (
                <div style={{ color: theme.colors.danger, fontSize: theme.fonts.sizeSm }}>
                    {error}
                </div>
            ) : (
                <>
                    <div className="chart-controls" style={{
                        display: 'flex',
                        justifyContent: 'space-between',
                        alignItems: 'center',
                        marginBottom: '0.5rem'
                    }}>
                        <div className="symbol-info" style={{ fontSize: theme.fonts.sizeSm }}>
                            <span style={{ 
                                fontWeight: theme.fonts.weight.semibold 
                            }}>
                                {symbol}
                            </span>
                            {realtimeData && (
                                <span style={{ 
                                    marginLeft: '0.5rem',
                                    color: parseFloat(realtimeData.change) >= 0 ? theme.colors.success : theme.colors.danger
                                }}>
                                    {parseFloat(realtimeData.price).toFixed(2)} 
                                    {' '}
                                    ({parseFloat(realtimeData.change) >= 0 ? '+' : ''}
                                    {parseFloat(realtimeData.changePercent).toFixed(2)}%)
                                </span>
                            )}
                        </div>
                        
                        <div className="timeframe-buttons" style={{
                            display: 'flex',
                            gap: '0.25rem'
                        }}>
                            {timeframeButtons.map(tf => (
                                <button
                                    key={tf}
                                    type="button"
                                    onClick={() => setTimeframe(tf)}
                                    style={{
                                        padding: '0.125rem 0.375rem',
                                        fontSize: theme.fonts.sizeXs,
                                        backgroundColor: timeframe === tf ? theme.colors.primary : 'transparent',
                                        color: timeframe === tf ? '#fff' : theme.colors.textSecondary,
                                        border: `1px solid ${timeframe === tf ? theme.colors.primary : theme.colors.border}`,
                                        borderRadius: '0.25rem',
                                        cursor: 'pointer'
                                    }}
                                >
                                    {tf}
                                </button>
                            ))}
                        </div>
                    </div>
                    
                    <div 
                        ref={chartContainerRef} 
                        className="chart-container"
                        style={{
                            height: '300px',
                            position: 'relative'
                        }}
                    >
                        {loading && (
                            <div style={{
                                position: 'absolute',
                                top: 0,
                                left: 0,
                                right: 0,
                                bottom: 0,
                                backgroundColor: `${theme.colors.background}80`,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                zIndex: 10
                            }}>
                                Yükleniyor...
                            </div>
                        )}
                    </div>
                </>
            )}
        </div>
    );
};

export default StockChart; 