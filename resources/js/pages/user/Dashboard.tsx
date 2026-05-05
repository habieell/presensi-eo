import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  Calendar, CheckCircle2, ClockIcon, Camera, ScanFace,
  TrendingUp, AlertTriangle, ArrowRight,
} from 'lucide-react';
import api from '../../lib/axios';
import { useAuth } from '../../contexts/AuthContext';
import { formatDate } from '../../lib/utils';

interface DashData {
  today: { check_in: any; check_out: any };
  month_stats: { present: number; late: number; early_leave: number };
  face_registered: boolean;
  upcoming_events: any[];
}

export default function UserDashboard() {
  const { user } = useAuth();
  const [data, setData] = useState<DashData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/user/dashboard').then((r) => setData(r.data)).finally(() => setLoading(false));
  }, []);

  return (
    <div className="space-y-5">
      {/* Welcome */}
      <div className="glass-card p-6 sm:p-7">
        <div className="flex items-center justify-between flex-wrap gap-4">
          <div>
            <p className="text-xs uppercase tracking-wider text-brand-400 font-semibold mb-1">Selamat Datang</p>
            <h1 className="text-2xl sm:text-3xl font-bold text-gradient">{user?.name}</h1>
            <p className="text-sm text-neutral-400 mt-1">{formatDate(new Date(), { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })}</p>
          </div>
          {data && !data.face_registered && (
            <Link to="/face-register" className="badge badge-warning">
              <AlertTriangle className="h-3 w-3" /> Wajah belum didaftarkan
            </Link>
          )}
        </div>
      </div>

      {/* Today's status */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <StatusCard
          title="Check-In Hari Ini"
          value={data?.today.check_in?.time ?? '-'}
          icon={<CheckCircle2 className="h-5 w-5 text-emerald-300" />}
          accent="emerald"
          subtitle={data?.today.check_in?.face_verified ? '✓ Face Verified' : 'Belum check-in'}
        />
        <StatusCard
          title="Check-Out Hari Ini"
          value={data?.today.check_out?.time ?? '-'}
          icon={<ClockIcon className="h-5 w-5 text-cyan-300" />}
          accent="cyan"
          subtitle={data?.today.check_out ? '✓ Selesai bekerja' : 'Belum check-out'}
        />
      </div>

      {/* Quick action */}
      <div className="grid grid-cols-2 gap-3">
        <Link to="/check-in" className="glass-card p-5 group">
          <Camera className="h-7 w-7 text-brand-400 mb-3 group-hover:scale-110 transition-transform" />
          <p className="font-bold text-white">Presensi</p>
          <p className="text-xs text-neutral-400 mt-1">Check-in / Check-out via verifikasi wajah</p>
          <ArrowRight className="h-4 w-4 text-brand-400/70 mt-3 group-hover:translate-x-1 transition" />
        </Link>
        <Link to="/face-register" className="glass-card p-5 group">
          <ScanFace className="h-7 w-7 text-brand-400 mb-3 group-hover:scale-110 transition-transform" />
          <p className="font-bold text-white">Daftar Wajah</p>
          <p className="text-xs text-neutral-400 mt-1">Perbarui foto wajah untuk presensi</p>
          <ArrowRight className="h-4 w-4 text-brand-400/70 mt-3 group-hover:translate-x-1 transition" />
        </Link>
      </div>

      {/* Month stats */}
      <div className="glass-card p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="font-bold text-white flex items-center gap-2">
            <TrendingUp className="h-4 w-4 text-brand-400" /> Statistik Bulan Ini
          </h3>
          <Link to="/riwayat" className="text-xs text-brand-400 hover:text-brand-300 flex items-center gap-1">
            Lihat semua <ArrowRight className="h-3 w-3" />
          </Link>
        </div>
        <div className="grid grid-cols-3 gap-3 text-center">
          <Stat label="Hadir" value={data?.month_stats.present ?? 0} color="emerald" />
          <Stat label="Terlambat" value={data?.month_stats.late ?? 0} color="amber" />
          <Stat label="Pulang Awal" value={data?.month_stats.early_leave ?? 0} color="rose" />
        </div>
      </div>

      {/* Upcoming events */}
      <div className="glass-card p-6">
        <h3 className="font-bold text-white flex items-center gap-2 mb-4">
          <Calendar className="h-4 w-4 text-brand-400" /> Acara Mendatang
        </h3>
        {!data?.upcoming_events?.length ? (
          <p className="text-sm text-neutral-500 text-center py-6">Tidak ada acara mendatang</p>
        ) : (
          <div className="space-y-2">
            {data.upcoming_events.map((e) => (
              <div key={e.id} className="p-3 rounded-xl bg-white/[0.02] border border-white/5">
                <p className="font-semibold text-white text-sm">{e.title}</p>
                <p className="text-xs text-neutral-400 mt-0.5">{new Date(e.event_at).toLocaleString('id-ID')}</p>
                {e.location && <p className="text-xs text-neutral-500">{e.location}</p>}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

function StatusCard({ title, value, icon, accent, subtitle }: { title: string; value: string; icon: React.ReactNode; accent: string; subtitle: string }) {
  return (
    <div className="glass-card p-5">
      <div className="flex items-start justify-between mb-3">
        <p className="text-xs uppercase tracking-wider text-brand-300/70 font-semibold">{title}</p>
        <div className={`h-9 w-9 rounded-xl bg-${accent}-500/15 flex items-center justify-center`}>{icon}</div>
      </div>
      <p className="text-3xl font-bold text-white">{value}</p>
      <p className={`text-xs mt-1 text-${accent}-300/80`}>{subtitle}</p>
    </div>
  );
}

function Stat({ label, value, color }: { label: string; value: number; color: string }) {
  return (
    <div className={`p-4 rounded-xl bg-${color}-500/10 border border-${color}-500/20`}>
      <p className={`text-2xl font-bold text-${color}-300`}>{value}</p>
      <p className="text-xs text-brand-200/70 mt-1">{label}</p>
    </div>
  );
}
