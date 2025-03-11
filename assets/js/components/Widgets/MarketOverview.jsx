import React, { useContext } from 'react';
import { ThemeContext } from '../../contexts/ThemeContext';
import useStockData from '../../hooks/useStockData';

const MarketOverview = () => {
    const { theme } = useContext(ThemeContext);
    const { data: stockData, loading, error } = useStockData(null, true);
    
    // Example market indices data (will be replaced with real data)
    const marketIndices = [
        { id: 'BIST100', name: 'BIST 100', value: '9,782.43', change: '+1.20', changePercent: '+1.20' },
        { id: 'BIST30', name: 'BIST 30', value: '11,423.56', change: '+0.87', changePercent: '+0.87' },
        { id: 'BISTBANK', name: 'BIST BANK', value: '3,245.12', change: '-0.35', changePercent: '-0.35' },
        { id: 'DOW', name: 'DOW JONES', value: '38,654.12', change: '+0.42', changePercent: '+0.42' },
        { id: 'DAX', name: 'DAX', value: '17,814.32', change: '+0.75', changePercent: '+0.75' },
        { id: 'EUR-USD', name: 'EUR/USD', value: '1.0823', change: '-0.12', changePercent: '-0.12' }
    ];
    
    // Function to determine color based on change
    const getChangeColor = (change) => {
        const numChange = Number.parseFloat(change);
        if (numChange > 0) return theme.colors.success;
        if (numChange < 0) return theme.colors.danger;
        return theme.colors.textSecondary;
    };
    
    return (
        <div className="market-overview-widget">
            <div className="indices-grid" style={{
                display: 'grid',
                gridTemplateColumns: 'repeat(2, 1fr)',
                gap: '0.75rem',
                fontSize: theme.fonts.sizeXs
            }}>
                {marketIndices.map(index => (
                    <div key={index.id} className="index-card" style={{
                        backgroundColor: theme.name === 'dark' ? `${theme.colors.cardBackground}80` : theme.colors.cardBackground,
                        border: `1px solid ${theme.colors.border}`,
                        borderRadius: '0.25rem',
                        padding: '0.5rem',
                        display: 'flex',
                        flexDirection: 'column'
                    }}>
                        <div style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center'
                        }}>
                            <div style={{ fontWeight: theme.fonts.weight.semibold }}>{index.name}</div>
                            <div style={{ 
                                color: getChangeColor(index.change),
                                fontWeight: theme.fonts.weight.medium
                            }}>
                                {Number.parseFloat(index.change) > 0 ? '+' : ''}
                                {index.changePercent}%
                            </div>
                        </div>
                        
                        <div style={{
                            display: 'flex',
                            justifyContent: 'space-between',
                            marginTop: '0.25rem'
                        }}>
                            <div style={{ fontSize: theme.fonts.sizeSm, fontWeight: theme.fonts.weight.bold }}>
                                {index.value}
                            </div>
                            <div style={{ 
                                color: getChangeColor(index.change),
                                fontSize: theme.fonts.sizeXs
                            }}>
                                {index.change}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            
            <div className="market-summary" style={{
                marginTop: '0.75rem',
                padding: '0.75rem',
                backgroundColor: theme.name === 'dark' ? `${theme.colors.cardBackground}80` : theme.colors.cardBackground,
                border: `1px solid ${theme.colors.border}`,
                borderRadius: '0.25rem',
                fontSize: theme.fonts.sizeXs
            }}>
                <div style={{
                    display: 'grid',
                    gridTemplateColumns: 'repeat(4, 1fr)',
                    gap: '0.5rem 1rem'
                }}>
                    <div>
                        <div style={{ color: theme.colors.textSecondary }}>Yükselen</div>
                        <div style={{ 
                            color: theme.colors.success, 
                            fontWeight: theme.fonts.weight.medium 
                        }}>267</div>
                    </div>
                    <div>
                        <div style={{ color: theme.colors.textSecondary }}>Düşen</div>
                        <div style={{ 
                            color: theme.colors.danger, 
                            fontWeight: theme.fonts.weight.medium 
                        }}>153</div>
                    </div>
                    <div>
                        <div style={{ color: theme.colors.textSecondary }}>Hacim</div>
                        <div style={{ fontWeight: theme.fonts.weight.medium }}>24.5B</div>
                    </div>
                    <div>
                        <div style={{ color: theme.colors.textSecondary }}>İşlem</div>
                        <div style={{ fontWeight: theme.fonts.weight.medium }}>1.2M</div>
                    </div>
                </div>
                
                <div style={{ 
                    marginTop: '0.5rem',
                    fontSize: theme.fonts.sizeXs,
                    color: theme.colors.textSecondary,
                    textAlign: 'right'
                }}>
                    Son Güncelleme: 14:32:45
                </div>
            </div>
        </div>
    );
};

export default MarketOverview; 