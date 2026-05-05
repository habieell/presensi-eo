import { useEffect, useState } from 'react';
import { Users, UserCheck, TrendingUp, TrendingDown, Calendar, Wallet } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, ResponsiveContainer, Tooltip, CartesianGrid } from 'recharts';
import api from '../../lib/axios';
import { formatRupiah, formatDateTime } from '../../lib/utils';

export default function AdminDashboard() {
  const [data, setData] = useState<any>(null);

  useEffect(() => { api.get('/admin/dashboard').then((r) => setData(r.data)); }, []);

  if (!data) return <div className="space-y-4">{[1, 2, 3].map((i) => <div key={i} className="h-32 skeleton rounded-2xl" />)}</div>;

  return (
    <div className="space-y-5">
      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
        <Stat icon={<Users className="h-5 w-5" />} label="Total User" value={data.stats.total_users} accent="brand" />
        <Stat icon={<UserCheck className="h-5 w-5" />} label="Hadir Hari Ini" value={data.stats.today_present} accent="emerald" />
        <Stat icon={<TrendingUp className="h-5 w-5" />} label="Pemasukan Bulan" value={formatRupiah(data.stats.monthly_income)} accent="cyan" />
        <Stat icon={<TrendingDown className="h-5 w-5" />} label="Pengeluaran Bulan" value={formatRupiah(data.stats.monthly_expense)} accent="rose" />
      </div>

      {/* Chart */}
      <div className="glass-card p-6">
        <h3 className="font-bold text-white mb-4 flex items-center gap-2"><TrendingUp className="h-4 w-4 text-brand-300" /> Kehadiran 7 Hari Terakhir</h3>
        <ResponsiveContainer width="100%" height={280}>
          <BarChart data={data.attendance_chart}>
            <defs>
              <linearGradient id="present" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stopColor="#fbbf24" stopOpacity={0.95} />
                <stop offset="100%" stopColor="#d97706" stopOpacity={0.75} />
              </linearGradient>
              <linearGradient id="late" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" stopColor="#ef4444" stopOpacity={0.95} />
                <stop offset="100%" stopColor="#b91c1c" stopOpacity={0.75} />
              </linearGradient>
            </defs>
            <CartesianGrid stroke="rgba(255, 255, 255, 0.06)" vertical={false} />
            <XAxis dataKey="label" stroke="#a3a3a3" tick={{ fontSize: 12 }} />
            <YAxis stroke="#a3a3a3" tick={{ fontSize: 12 }} />
            <Tooltip
              contentStyle={{ background: 'rgba(10, 10, 10, 0.95)', border: '1px solid rgba(251, 191, 36, 0.3)', borderRadius: 12, backdropFilter: 'blur(20px)' }}
              labelStyle={{ color: '#fcd34d' }}
            />
            <Bar dataKey="present" fill="url(#present)" radius={[8, 8, 0, 0]} name="Hadir" />
            <Bar dataKey="late" fill="url(#late)" radius={[8, 8, 0, 0]} name="Terlambat" />
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* Upcoming events */}
      <div className="glass-card p-6">
        <h3 className="font-bold text-white mb-4 flex items-center gap-2"><Calendar className="h-4 w-4 text-cyan-300" /> Event Mendatang</h3>
        {data.upcoming_events?.length === 0 ? (
          <p className="text-sm text-brand-200/60 text-center py-4">Belum ada event</p>
        ) : (
          <div className="space-y-2">
            {data.upcoming_events.map((e: any) => (
              <div key={e.id} className="p-3 rounded-xl bg-surface-200/40 border border-brand-400/10 flex items-center justify-between">
                <div>
                  <p className="font-semibold text-white text-sm">{e.title}</p>
                  <p className="text-xs text-brand-200/70">{formatDateTime(e.event_at)}</p>
                </div>
                <span className="badge badge-info">{e.category}</span>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}

function Stat({ icon, label, value, accent }: { icon: React.ReactNode; label: string; value: any; accent: string }) {
  const map: any = {
    brand: 'text-brand-400 bg-brand-500/15',
    emerald: 'text-emerald-400 bg-emerald-500/10',
    cyan: 'text-brand-400 bg-brand-500/10',
    rose: 'text-rose-400 bg-rose-500/10',
  };
  return (
    <div className="glass-card p-4">
      <div className="flex items-center justify-between mb-2">
        <p className="text-[10px] uppercase tracking-wider text-neutral-500 font-bold">{label}</p>
        <div className={`h-8 w-8 rounded-lg flex items-center justify-center ${map[accent]}`}>{icon}</div>
      </div>
      <p className="text-xl sm:text-2xl font-bold text-white">{value}</p>
    </div>
  );
}
