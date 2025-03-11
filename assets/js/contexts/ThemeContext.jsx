import React, { createContext, useState, useEffect } from 'react';

// Define theme colors and styles
export const themes = {
    light: {
        name: 'light',
        colors: {
            primary: '#2563eb', // Blue
            secondary: '#9ca3af', // Gray
            accent: '#f59e0b', // Amber
            background: '#f9fafb', // Light gray
            cardBackground: '#ffffff',
            text: '#111827', // Dark gray
            textSecondary: '#6b7280', // Medium gray
            border: '#e5e7eb', // Light gray
            success: '#10b981', // Green
            danger: '#ef4444', // Red
            chart: {
                background: '#ffffff',
                grid: '#e5e7eb',
                text: '#6b7280',
                up: '#10b981',
                down: '#ef4444',
                volume: '#9ca3af'
            }
        },
        fonts: {
            sizeXs: '0.65rem',
            sizeSm: '0.75rem',
            sizeBase: '0.875rem',
            sizeLg: '1rem',
            sizeXl: '1.125rem',
            weight: {
                light: 300,
                normal: 400,
                medium: 500,
                semibold: 600,
                bold: 700
            }
        }
    },
    dark: {
        name: 'dark',
        colors: {
            primary: '#3b82f6', // Blue
            secondary: '#6b7280', // Gray
            accent: '#f59e0b', // Amber
            background: '#111827', // Very dark gray
            cardBackground: '#1f2937', // Dark gray
            text: '#f9fafb', // Light gray
            textSecondary: '#9ca3af', // Medium gray
            border: '#374151', // Medium dark gray
            success: '#10b981', // Green
            danger: '#ef4444', // Red
            chart: {
                background: '#1f2937',
                grid: '#374151',
                text: '#9ca3af',
                up: '#10b981',
                down: '#ef4444',
                volume: '#6b7280'
            }
        },
        fonts: {
            sizeXs: '0.65rem',
            sizeSm: '0.75rem',
            sizeBase: '0.875rem',
            sizeLg: '1rem',
            sizeXl: '1.125rem',
            weight: {
                light: 300,
                normal: 400,
                medium: 500,
                semibold: 600,
                bold: 700
            }
        }
    }
};

// Create Context
export const ThemeContext = createContext();

export const ThemeProvider = ({ children }) => {
    // Check local storage for saved theme preference or use system preference
    const getInitialTheme = () => {
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme && Object.keys(themes).includes(savedTheme)) {
            return savedTheme;
        }
        
        // Check system preference
        if (window.matchMedia?.('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        
        return 'light'; // Default theme
    };

    const [currentTheme, setCurrentTheme] = useState(getInitialTheme);
    
    // Apply theme class to document body
    useEffect(() => {
        const root = document.documentElement;
        
        // Remove old theme classes
        for (const themeName of Object.keys(themes)) {
            root.classList.remove(`theme-${themeName}`);
        }
        
        // Add current theme class
        root.classList.add(`theme-${currentTheme}`);
        
        // Save to local storage
        localStorage.setItem('theme', currentTheme);
    }, [currentTheme]);

    // Toggle theme function
    const toggleTheme = () => {
        setCurrentTheme(prevTheme => prevTheme === 'light' ? 'dark' : 'light');
    };

    return (
        <ThemeContext.Provider value={{ 
            theme: themes[currentTheme], 
            toggleTheme,
            isDark: currentTheme === 'dark'
        }}>
            {children}
        </ThemeContext.Provider>
    );
}; 