import React from 'react';
import { createRoot } from 'react-dom/client';
import { ThemeProvider } from './contexts/ThemeContext';
import Dashboard from './components/Dashboard/Dashboard';
import './styles/app.css';

const App = () => {
    return (
        <ThemeProvider>
            <Dashboard />
        </ThemeProvider>
    );
};

const container = document.getElementById('app');
const root = createRoot(container);
root.render(<App />); 