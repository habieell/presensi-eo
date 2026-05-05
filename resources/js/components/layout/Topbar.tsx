import { useNavigate } from "react-router-dom";
import { LogOut, ShieldCheck, Sparkles } from "lucide-react";
import { useAuth } from "../../contexts/AuthContext";
import { getInitials } from "../../lib/utils";

export default function Topbar() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate("/login");
  };

  return (
    <header className="sticky top-0 z-40">
      <div className="glass-strong border-b border-brand-400/10">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="h-10 w-10 rounded-xl bg-brand-gradient flex items-center justify-center shadow-glow">
              <Sparkles className="h-5 w-5 text-white" />
            </div>
            <div>
              <p className="text-sm font-bold text-gradient leading-tight">
                Presensi
              </p>
              <p className="text-[10px] uppercase tracking-wider text-brand-300/70 font-semibold">
                {user?.role === "admin" ? "Admin Console" : "Employee Portal"}
              </p>
            </div>
          </div>

          <div className="flex items-center gap-3">
            {user?.role === "admin" && (
              <span className="badge badge-info hidden sm:inline-flex">
                <ShieldCheck className="h-3 w-3" />
                ADMIN
              </span>
            )}
            <div className="flex items-center gap-2">
              <div className="h-9 w-9 rounded-full bg-brand-gradient-vivid text-white flex items-center justify-center text-xs font-bold shadow-glow-cyan">
                {user ? getInitials(user.name) : "??"}
              </div>
              <div className="hidden sm:block text-right">
                <p className="text-xs font-semibold text-brand-100 leading-tight">
                  {user?.name}
                </p>
                <p className="text-[10px] text-brand-300/60">
                  {user?.position ?? user?.email}
                </p>
              </div>
            </div>
            <button
              onClick={handleLogout}
              className="btn-ghost p-2"
              title="Logout"
            >
              <LogOut className="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </header>
  );
}
