import { NavLink, useLocation } from "react-router-dom";
import { motion, AnimatePresence } from "framer-motion";
import {
  CalendarDays,
  Camera,
  ClipboardList,
  Coins,
  Home,
  LayoutDashboard,
  Users,
  Wallet,
  FileClock,
  ClockIcon,
} from "lucide-react";

interface NavItem {
  to: string;
  label: string;
  Icon: React.ComponentType<{ className?: string; strokeWidth?: number }>;
}

const userNav: NavItem[] = [
  { to: "/dashboard", label: "Beranda", Icon: Home },
  { to: "/check-in", label: "Presensi", Icon: Camera },
  { to: "/calendar", label: "Kalender", Icon: CalendarDays },
  { to: "/cash-flow", label: "Kas", Icon: Wallet },
  { to: "/profile", label: "Profil", Icon: ClipboardList },
];

const adminNav: NavItem[] = [
  { to: "/admin", label: "Dashboard", Icon: LayoutDashboard },
  { to: "/admin/users", label: "User", Icon: Users },
  { to: "/admin/attendance", label: "Absensi", Icon: ClockIcon },
  { to: "/admin/cash-flow", label: "Kas", Icon: Coins },
  { to: "/admin/events", label: "Acara", Icon: CalendarDays },
  { to: "/admin/logs", label: "Log", Icon: FileClock },
];

const SPRING = {
  type: "spring" as const,
  stiffness: 380,
  damping: 32,
  mass: 0.6,
};

export default function BottomNavbar({ isAdmin }: { isAdmin: boolean }) {
  const items = isAdmin ? adminNav : userNav;
  const location = useLocation();

  // Cari item yang aktif
  const activeIndex = items.findIndex((it) =>
    it.to === "/admin"
      ? location.pathname === "/admin"
      : location.pathname.startsWith(it.to),
  );

  return (
    <nav className="bottom-nav">
      <div className="max-w-6xl mx-auto flex items-stretch justify-between gap-1 relative">
        {items.map((item, idx) => {
          const isActive = idx === activeIndex;
          return (
            <NavItemAnimated key={item.to} item={item} isActive={isActive} />
          );
        })}
      </div>
    </nav>
  );
}

function NavItemAnimated({
  item,
  isActive,
}: {
  item: NavItem;
  isActive: boolean;
}) {
  const { to, label, Icon } = item;
  return (
    <NavLink
      to={to}
      end={to === "/admin"}
      className="relative flex-1 min-w-0 flex flex-col items-center justify-center py-2 px-1.5 rounded-2xl"
    >
      {/* Background pill yang slide-in */}
      <AnimatePresence>
        {isActive && (
          <motion.span
            layoutId="bottomnav-active-bg"
            className="absolute inset-0 rounded-2xl bg-gradient-to-br from-brand-300 via-brand-400 to-brand-600"
            transition={SPRING}
            style={{
              boxShadow:
                "0 8px 24px rgba(251, 191, 36, 0.45), inset 0 1px 0 rgba(255,255,255,0.30)",
            }}
          />
        )}
      </AnimatePresence>

      {/* Indicator bar di atas */}
      <AnimatePresence>
        {isActive && (
          <motion.span
            layoutId="bottomnav-indicator-bar"
            className="absolute -top-1 left-1/2 h-1 w-7 rounded-full bg-gradient-to-r from-brand-200 to-brand-400"
            style={{ x: "-50%", boxShadow: "0 0 12px rgba(251,191,36,0.8)" }}
            transition={SPRING}
          />
        )}
      </AnimatePresence>

      {/* Icon dengan bounce */}
      <motion.span
        className="relative z-10 flex items-center justify-center"
        animate={
          isActive ? { scale: [1, 1.25, 1], rotate: [0, -8, 0] } : { scale: 1 }
        }
        transition={
          isActive ? { duration: 0.45, ease: "easeOut" } : { duration: 0.2 }
        }
      >
        <Icon
          className={`transition-colors duration-300 ${
            isActive ? "text-black" : "text-neutral-500"
          } ${isActive ? "h-5 w-5" : "h-5 w-5"}`}
          strokeWidth={isActive ? 2.5 : 2}
        />
      </motion.span>

      {/* Label dengan slide-up untuk active */}
      <motion.span
        className={`relative z-10 mt-0.5 text-[0.65rem] font-semibold whitespace-nowrap leading-none ${
          isActive ? "text-black" : "text-neutral-500"
        }`}
        initial={false}
        animate={
          isActive
            ? { y: 0, opacity: 1, fontWeight: 800 }
            : { y: 0, opacity: 0.85, fontWeight: 600 }
        }
        transition={{ duration: 0.25 }}
      >
        {label}
      </motion.span>

      {/* Ripple subtle saat hover */}
      <motion.span
        className="absolute inset-0 rounded-2xl pointer-events-none"
        initial={{ opacity: 0 }}
        whileHover={
          !isActive
            ? { opacity: 1, backgroundColor: "rgba(251, 191, 36, 0.06)" }
            : {}
        }
        transition={{ duration: 0.2 }}
      />
    </NavLink>
  );
}
