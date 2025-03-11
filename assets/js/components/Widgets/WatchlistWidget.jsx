import React, { useState, useContext, useEffect } from 'react';
import { ThemeContext } from '../../contexts/ThemeContext';
import useStockData from '../../hooks/useStockData';

const WatchlistWidget = () => {
    const { theme } = useContext(ThemeContext);
    const [watchlist, setWatchlist] = useState(() => {
        // Get watchlist from localStorage or use default watchlist
        try {
            const savedWatchlist = localStorage.getItem('watchlist');
            return savedWatchlist ? JSON.parse(savedWatchlist) : [
                'GARAN', 'ASELS', 'SISE', 'KRDMD', 'THYAO'
            ];
        } catch (e) {
            console.error('Error loading watchlist from localStorage:', e);
            return ['GARAN', 'ASELS', 'SISE', 'KRDMD', 'THYAO'];
        }
    });

    // Save watchlist to localStorage when it changes
    useEffect(() => {
        localStorage.setItem('watchlist', JSON.stringify(watchlist));
    }, [watchlist]);

    // Mock data for watchlist stocks
    const mockStockData = {
        'GARAN': { 
            price: '22.46', 
            change: '+0.46', 
            changePercent: '+2.09', 
            volume: '156.2M' 
        },
        'ASELS': { 
            price: '46.92', 
            change: '-0.82', 
            changePercent: '-1.72', 
            volume: '87.5M' 
        },
        'SISE': { 
            price: '17.34', 
            change: '+0.22', 
            changePercent: '+1.28', 
            volume: '52.1M' 
        },
        'KRDMD': { 
            price: '8.62', 
            change: '+0.08', 
            changePercent: '+0.94', 
            volume: '113.7M' 
        },
        'THYAO': { 
            price: '117.90', 
            change: '-1.70', 
            changePercent: '-1.42', 
            volume: '94.3M' 
        },
    };

    // Function to add stock to watchlist
    const addToWatchlist = (symbol) => {
        if (!watchlist.includes(symbol)) {
            setWatchlist([...watchlist, symbol]);
        }
    };

    // Function to remove stock from watchlist
    const removeFromWatchlist = (symbol) => {
        setWatchlist(watchlist.filter(item => item !== symbol));
    };

    // Function to get text color based on change
    const getChangeColor = (change) => {
        const numChange = Number.parseFloat(change);
        if (numChange > 0) return theme.colors.success;
        if (numChange < 0) return theme.colors.danger;
        return theme.colors.textSecondary;
    };

    // New stock input
    const [newStock, setNewStock] = useState('');
    
    // Handle adding new stock
    const handleAddStock = (e) => {
        e.preventDefault();
        if (newStock.trim() && !watchlist.includes(newStock.trim().toUpperCase())) {
            addToWatchlist(newStock.trim().toUpperCase());
            setNewStock('');
        }
    };

    return (
        <div className="watchlist-widget">
            <form 
                onSubmit={handleAddStock}
                style={{
                    display: 'flex',
                    marginBottom: '0.75rem'
                }}
            >
                <input
                    type="text"
                    value={newStock}
                    onChange={(e) => setNewStock(e.target.value)}
                    placeholder="Sembol ekle... (ör. EREGL)"
                    style={{
                        flex: 1,
                        padding: '0.375rem 0.5rem',
                        fontSize: theme.fonts.sizeXs,
                        backgroundColor: theme.colors.background,
                        color: theme.colors.text,
                        border: `1px solid ${theme.colors.border}`,
                        borderRadius: '0.25rem 0 0 0.25rem',
                        outline: 'none'
                    }}
                />
                <button
                    type="submit"
                    style={{
                        padding: '0.375rem 0.5rem',
                        backgroundColor: theme.colors.primary,
                        color: '#fff',
                        border: `1px solid ${theme.colors.primary}`,
                        borderRadius: '0 0.25rem 0.25rem 0',
                        fontSize: theme.fonts.sizeXs,
                        cursor: 'pointer'
                    }}
                >
                    Ekle
                </button>
            </form>
            
            <div className="watchlist-table" style={{
                fontSize: theme.fonts.sizeXs
            }}>
                <div className="watchlist-header" style={{
                    display: 'grid',
                    gridTemplateColumns: 'minmax(60px, 1fr) minmax(60px, 1fr) minmax(70px, 1fr) 20px',
                    padding: '0.25rem 0.5rem',
                    fontWeight: theme.fonts.weight.medium,
                    borderBottom: `1px solid ${theme.colors.border}`,
                    color: theme.colors.textSecondary
                }}>
                    <div>Sembol</div>
                    <div style={{ textAlign: 'right' }}>Fiyat</div>
                    <div style={{ textAlign: 'right' }}>Değişim</div>
                    <div />
                </div>
                
                {watchlist.map(symbol => {
                    const stock = mockStockData[symbol];
                    if (!stock) return null;
                    
                    return (
                        <div 
                            key={symbol}
                            className="watchlist-item"
                            style={{
                                display: 'grid',
                                gridTemplateColumns: 'minmax(60px, 1fr) minmax(60px, 1fr) minmax(70px, 1fr) 20px',
                                padding: '0.5rem',
                                borderBottom: `1px solid ${theme.colors.border}`,
                                alignItems: 'center'
                            }}
                        >
                            <div style={{ 
                                fontWeight: theme.fonts.weight.semibold
                            }}>
                                {symbol}
                            </div>
                            <div style={{ 
                                textAlign: 'right',
                                fontWeight: theme.fonts.weight.medium
                            }}>
                                {stock.price}
                            </div>
                            <div style={{ 
                                textAlign: 'right',
                                color: getChangeColor(stock.change),
                                fontWeight: theme.fonts.weight.medium
                            }}>
                                {stock.change} ({stock.changePercent}%)
                            </div>
                            <button
                                type="button"
                                onClick={() => removeFromWatchlist(symbol)}
                                style={{
                                    background: 'none',
                                    border: 'none',
                                    color: theme.colors.textSecondary,
                                    cursor: 'pointer',
                                    fontSize: theme.fonts.sizeXs,
                                    padding: 0,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center'
                                }}
                                aria-label={`Remove ${symbol} from watchlist`}
                            >
                                ✕
                            </button>
                        </div>
                    );
                })}
                
                {watchlist.length === 0 && (
                    <div style={{
                        padding: '1rem 0.5rem',
                        textAlign: 'center',
                        color: theme.colors.textSecondary,
                        fontSize: theme.fonts.sizeXs
                    }}>
                        İzleme listenize hisse ekleyin.
                    </div>
                )}
            </div>
        </div>
    );
};

export default WatchlistWidget; 