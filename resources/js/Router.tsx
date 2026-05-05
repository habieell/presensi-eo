import { Navigate, Route, Routes } from 'react-router-dom';
import { useAuth } from './contexts/AuthContext';

import Login from './pages/auth/Login';
import AppLayout from './components/layout/AppLayout';

// User pages
import UserDashboard from './pages/user/Dashboard';
import CheckIn from './pages/user/CheckIn';
import FaceRegister from './pages/user/FaceRegister';
import UserProfile from './pages/user/Profile';
import UserCalendar from './pages/user/Calendar';
import UserCashFlow from './pages/user/CashFlow';
import RiwayatAbsen from './pages/user/RiwayatAbsen';

// Admin pages
import AdminDashboard from './pages/admin/Dashboard';
import AdminUsers from './pages/admin/Users';
import AdminAttendance from './pages/admin/Attendance';
import AdminCashFlow from './pages/admin/CashFlow';
import AdminEvents from './pages/admin/Events';
import AdminLogs from './pages/admin/Logs';
import AdminSettings from './pages/admin/Settings';

function RequireAuth({ children, adminOnly = false }: { children: JSX.Element; adminOnly?: boolean }) {
  const { user, loading } = useAuth();
  if (loading) return <FullPageLoader />;
  if (!user) return <Navigate to="/login" replace />;
  if (adminOnly && user.role !== 'admin') return <Navigate to="/dashboard" replace />;
  return children;
}

function FullPageLoader() {
  return (
    <div className="flex h-screen items-center justify-center">
      <div className="glass-card p-8 flex items-center gap-3">
        <div className="h-5 w-5 rounded-full border-2 border-brand-300 border-t-transparent animate-spin"></div>
        <span className="text-brand-200">Memuat...</span>
      </div>
    </div>
  );
}

export default function Router() {
  const { user } = useAuth();
  return (
    <Routes>
      <Route path="/login" element={user ? <Navigate to={user.role === 'admin' ? '/admin' : '/dashboard'} replace /> : <Login />} />

      {/* User routes */}
      <Route path="/" element={<RequireAuth><AppLayout /></RequireAuth>}>
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="dashboard" element={<UserDashboard />} />
        <Route path="check-in" element={<CheckIn />} />
        <Route path="face-register" element={<FaceRegister />} />
        <Route path="profile" element={<UserProfile />} />
        <Route path="calendar" element={<UserCalendar />} />
        <Route path="cash-flow" element={<UserCashFlow />} />
        <Route path="riwayat" element={<RiwayatAbsen />} />
      </Route>

      {/* Admin routes */}
      <Route path="/admin" element={<RequireAuth adminOnly><AppLayout /></RequireAuth>}>
        <Route index element={<AdminDashboard />} />
        <Route path="users" element={<AdminUsers />} />
        <Route path="attendance" element={<AdminAttendance />} />
        <Route path="cash-flow" element={<AdminCashFlow />} />
        <Route path="events" element={<AdminEvents />} />
        <Route path="logs" element={<AdminLogs />} />
        <Route path="settings" element={<AdminSettings />} />
      </Route>

      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  );
}
