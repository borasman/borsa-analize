import React, { useState, useContext } from 'react';
import { Draggable } from 'react-beautiful-dnd';
import { ThemeContext } from '../../contexts/ThemeContext';

const WidgetCard = ({ widget, index, children }) => {
    const { theme } = useContext(ThemeContext);
    const [isExpanded, setIsExpanded] = useState(true);
    
    // Calculate grid row size based on widget height
    const getHeight = () => {
        if (!isExpanded) return 'auto';
        return widget.h ? `${widget.h * 120}px` : 'auto';
    };

    return (
        <Draggable draggableId={widget.id} index={index}>
            {(provided, snapshot) => (
                <div
                    className={`widget-card ${snapshot.isDragging ? 'dragging' : ''}`}
                    ref={provided.innerRef}
                    {...provided.draggableProps}
                    style={{
                        ...provided.draggableProps.style,
                        backgroundColor: theme.colors.cardBackground,
                        borderRadius: '0.375rem',
                        border: `1px solid ${theme.colors.border}`,
                        overflow: 'hidden',
                        boxShadow: snapshot.isDragging ? '0 10px 15px -3px rgba(0, 0, 0, 0.1)' : 'none',
                        transition: 'box-shadow 0.2s, transform 0.2s',
                        height: getHeight(),
                        opacity: snapshot.isDragging ? 0.8 : 1
                    }}
                >
                    <div 
                        className="widget-header"
                        {...provided.dragHandleProps}
                        style={{
                            padding: '0.5rem 0.75rem',
                            borderBottom: `1px solid ${theme.colors.border}`,
                            backgroundColor: theme.colors.cardBackground,
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'center',
                            fontSize: theme.fonts.sizeSm,
                            fontWeight: theme.fonts.weight.semibold,
                            cursor: 'grab'
                        }}
                    >
                        <span>{widget.title}</span>
                        <div className="widget-actions">
                            <button
                                type="button"
                                onClick={() => setIsExpanded(!isExpanded)}
                                style={{
                                    background: 'none',
                                    border: 'none',
                                    cursor: 'pointer',
                                    color: theme.colors.textSecondary,
                                    fontSize: theme.fonts.sizeSm,
                                    padding: '0.25rem'
                                }}
                            >
                                {isExpanded ? '▲' : '▼'}
                            </button>
                        </div>
                    </div>
                    
                    {isExpanded && (
                        <div 
                            className="widget-content"
                            style={{
                                padding: '0.75rem',
                                overflow: 'auto'
                            }}
                        >
                            {children}
                        </div>
                    )}
                </div>
            )}
        </Draggable>
    );
};

export default WidgetCard; 