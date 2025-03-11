import React, { useContext } from 'react';
import { ThemeContext } from '../../contexts/ThemeContext';

const Header = () => {
    const { theme, toggleTheme, isDark } = useContext(ThemeContext);
    
    return (
        <header style={{
            backgroundColor: theme.colors.cardBackground,
            borderBottom: `1px solid ${theme.colors.border}`,
            padding: '0.75rem 1.5rem',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center'
        }}>
            <div className="logo" style={{
                fontSize: theme.fonts.sizeLg,
                fontWeight: theme.fonts.weight.bold,
                color: theme.colors.primary
            }}>
                Borsa Analize
            </div>
            
            <div className="header-right" style={{
                display: 'flex',
                alignItems: 'center',
                gap: '1rem'
            }}>
                <div className="market-status" style={{
                    fontSize: theme.fonts.sizeXs,
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'flex-end'
                }}>
                    <span style={{ color: theme.colors.success }}>BIST 100: 9,782.43 (+1.2%)</span>
                    <span style={{ color: theme.colors.textSecondary, fontSize: theme.fonts.sizeXs }}>
                        Son Güncelleme: 14:32:45
                    </span>
                </div>
                
                <button 
                    type="button"
                    onClick={toggleTheme}
                    style={{
                        backgroundColor: 'transparent',
                        border: 'none',
                        cursor: 'pointer',
                        color: theme.colors.text,
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        width: '2rem',
                        height: '2rem',
                        borderRadius: '50%',
                        padding: 0
                    }}
                    aria-label={isDark ? 'Switch to light theme' : 'Switch to dark theme'}
                >
                    {isDark ? '☀️' : '🌙'}
                </button>
                
                <div className="user-menu" style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '0.5rem',
                    fontSize: theme.fonts.sizeSm
                }}>
                    <span style={{ color: theme.colors.textSecondary }}>Kullanıcı</span>
                    <div style={{
                        backgroundColor: theme.colors.primary,
                        color: '#fff',
                        width: '2rem',
                        height: '2rem',
                        borderRadius: '50%',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        fontWeight: theme.fonts.weight.medium
                    }}>
                        UA
                    </div>
                </div>
            </div>
        </header>
    );
};

export default Header; 