import { Outlet, useLocation } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import BottomNavbar from './BottomNavbar';
import Topbar from './Topbar';

export default function AppLayout() {
  const { user } = useAuth();
  const location = useLocation();
  const isAdmin = user?.role === 'admin' || location.pathname.startsWith('/admin');

  return (
    <div className="min-h-screen flex flex-col">
      <Topbar />
      <main className="flex-1 max-w-6xl mx-auto w-full px-4 sm:px-6 lg:px-8 pt-4 pb-28">
        <Outlet />
      </main>
      <BottomNavbar isAdmin={isAdmin} />
    </div>
  );
}
