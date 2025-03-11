import React, { useState, useContext, useEffect } from 'react';
import { DragDropContext, Droppable } from 'react-beautiful-dnd';
import { ThemeContext } from '../../contexts/ThemeContext';
import WidgetCard from './WidgetCard';
import Header from '../Layout/Header';
import Sidebar from '../Layout/Sidebar';
import MarketOverview from '../Widgets/MarketOverview';
import StockChart from '../Widgets/StockChart';
import StockTable from '../Widgets/StockTable';
import NewsFeed from '../Widgets/NewsFeed';
import PortfolioSummary from '../Widgets/PortfolioSummary';
import WatchlistWidget from '../Widgets/WatchlistWidget';

const Dashboard = () => {
    const { theme } = useContext(ThemeContext);
    
    // Initial widget layout
    const [widgets, setWidgets] = useState(() => {
        // Try to load saved layout from localStorage
        const savedLayout = localStorage.getItem('dashboardLayout');
        if (savedLayout) {
            try {
                return JSON.parse(savedLayout);
            } catch (e) {
                console.error('Failed to parse saved dashboard layout:', e);
            }
        }
        
        // Default widget layout
        return {
            column1: [
                { id: 'market-overview', title: 'Piyasa Genel Görünümü', type: 'MarketOverview', h: 2 },
                { id: 'portfolio', title: 'Portföy Özeti', type: 'PortfolioSummary', h: 2 }
            ],
            column2: [
                { id: 'chart', title: 'BIST100 Grafiği', type: 'StockChart', h: 3, symbol: 'BIST100' }
            ],
            column3: [
                { id: 'watchlist', title: 'İzleme Listesi', type: 'WatchlistWidget', h: 2 },
                { id: 'news', title: 'Haberler', type: 'NewsFeed', h: 2 }
            ]
        };
    });

    // Save layout to localStorage when it changes
    useEffect(() => {
        localStorage.setItem('dashboardLayout', JSON.stringify(widgets));
    }, [widgets]);

    // Handle drag end event
    const handleDragEnd = (result) => {
        const { source, destination } = result;
        
        // Dropped outside a droppable area
        if (!destination) return;
        
        // Moved within the same column
        if (source.droppableId === destination.droppableId) {
            const column = [...widgets[source.droppableId]];
            const [removed] = column.splice(source.index, 1);
            column.splice(destination.index, 0, removed);
            
            setWidgets({
                ...widgets,
                [source.droppableId]: column
            });
        } 
        // Moved to another column
        else {
            const sourceColumn = [...widgets[source.droppableId]];
            const destColumn = [...widgets[destination.droppableId]];
            const [removed] = sourceColumn.splice(source.index, 1);
            
            destColumn.splice(destination.index, 0, removed);
            
            setWidgets({
                ...widgets,
                [source.droppableId]: sourceColumn,
                [destination.droppableId]: destColumn
            });
        }
    };

    // Render widget based on type
    const renderWidget = (widget) => {
        switch (widget.type) {
            case 'MarketOverview':
                return <MarketOverview />;
            case 'StockChart':
                return <StockChart symbol={widget.symbol} />;
            case 'StockTable':
                return <StockTable />;
            case 'NewsFeed':
                return <NewsFeed />;
            case 'PortfolioSummary':
                return <PortfolioSummary />;
            case 'WatchlistWidget':
                return <WatchlistWidget />;
            default:
                return <div>Unknown widget type: {widget.type}</div>;
        }
    };

    return (
        <div className="dashboard" style={{ 
            backgroundColor: theme.colors.background,
            color: theme.colors.text,
            minHeight: '100vh'
        }}>
            <Header />
            
            <div className="dashboard-content" style={{ display: 'flex' }}>
                <Sidebar />
                
                <main className="dashboard-main" style={{ flex: 1, padding: '1rem' }}>
                    <DragDropContext onDragEnd={handleDragEnd}>
                        <div className="dashboard-grid" style={{ 
                            display: 'grid',
                            gridTemplateColumns: 'repeat(3, 1fr)',
                            gap: '1rem'
                        }}>
                            {Object.keys(widgets).map((columnId) => (
                                <Droppable key={columnId} droppableId={columnId}>
                                    {(provided) => (
                                        <div
                                            className="dashboard-column"
                                            ref={provided.innerRef}
                                            {...provided.droppableProps}
                                            style={{ 
                                                minHeight: '80vh',
                                                display: 'flex',
                                                flexDirection: 'column',
                                                gap: '1rem'
                                            }}
                                        >
                                            {widgets[columnId].map((widget, index) => (
                                                <WidgetCard 
                                                    key={widget.id}
                                                    widget={widget}
                                                    index={index}
                                                >
                                                    {renderWidget(widget)}
                                                </WidgetCard>
                                            ))}
                                            {provided.placeholder}
                                        </div>
                                    )}
                                </Droppable>
                            ))}
                        </div>
                    </DragDropContext>
                </main>
            </div>
        </div>
    );
};

export default Dashboard; 