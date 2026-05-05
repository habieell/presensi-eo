import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { Toaster } from 'sonner';

import Router from './Router';
import { AuthProvider } from './contexts/AuthContext';

import './styles/globals.css';

const rootEl = document.getElementById('root')!;
createRoot(rootEl).render(
  <StrictMode>
    <BrowserRouter>
      <AuthProvider>
        <Router />
        <Toaster
          position="top-center"
          richColors
          theme="dark"
          toastOptions={{
            style: {
              background: 'rgba(30, 27, 75, 0.85)',
              backdropFilter: 'blur(20px)',
              border: '1px solid rgba(167, 139, 250, 0.25)',
              color: '#f5f3ff',
            },
          }}
        />
      </AuthProvider>
    </BrowserRouter>
  </StrictMode>,
);
