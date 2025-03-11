import React, { useContext } from 'react';
import { ThemeContext } from '../../contexts/ThemeContext';

const Sidebar = () => {
    const { theme } = useContext(ThemeContext);
    
    const navItems = [
        { id: 'dashboard', label: 'Dashboard', icon: '📊', active: true },
        { id: 'stocks', label: 'Hisseler', icon: '📈' },
        { id: 'portfolio', label: 'Portföy', icon: '💼' },
        { id: 'watchlist', label: 'İzleme Listesi', icon: '👀' },
        { id: 'news', label: 'Haberler', icon: '📰' },
        { id: 'alerts', label: 'Bildirimler', icon: '🔔' },
        { id: 'settings', label: 'Ayarlar', icon: '⚙️' }
    ];
    
    return (
        <aside style={{
            width: '220px',
            backgroundColor: theme.colors.cardBackground,
            borderRight: `1px solid ${theme.colors.border}`,
            height: 'calc(100vh - 61px)', // Adjust height based on header height
            padding: '1rem 0',
            display: 'flex',
            flexDirection: 'column'
        }}>
            <nav>
                <ul style={{
                    listStyle: 'none',
                    padding: 0,
                    margin: 0
                }}>
                    {navItems.map(item => (
                        <li key={item.id}>
                            <a 
                                href={`#${item.id}`}
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: '0.75rem',
                                    padding: '0.75rem 1.5rem',
                                    textDecoration: 'none',
                                    color: item.active ? theme.colors.primary : theme.colors.text,
                                    backgroundColor: item.active ? (
                                        theme.name === 'dark' ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.05)'
                                    ) : 'transparent',
                                    borderLeft: item.active ? `3px solid ${theme.colors.primary}` : '3px solid transparent',
                                    fontSize: theme.fonts.sizeSm,
                                    transition: 'all 0.2s',
                                    fontWeight: item.active ? theme.fonts.weight.medium : theme.fonts.weight.normal
                                }}
                            >
                                <span style={{ fontSize: '1.2rem' }}>{item.icon}</span>
                                {item.label}
                            </a>
                        </li>
                    ))}
                </ul>
            </nav>
            
            <div className="sidebar-footer" style={{
                marginTop: 'auto',
                padding: '1rem 1.5rem',
                borderTop: `1px solid ${theme.colors.border}`,
                fontSize: theme.fonts.sizeXs,
                color: theme.colors.textSecondary
            }}>
                <div>Borsa Analize v1.0</div>
                <div style={{ marginTop: '0.25rem' }}>© 2023 Tüm Hakları Saklıdır</div>
            </div>
        </aside>
    );
};

export default Sidebar; 