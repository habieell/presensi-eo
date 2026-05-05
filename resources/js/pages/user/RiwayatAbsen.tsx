import { useEffect, useState } from 'react';
import { Calendar, Clock, CheckCircle2, XCircle } from 'lucide-react';
import api from '../../lib/axios';
import { formatDate } from '../../lib/utils';

export default function RiwayatAbsen() {
  const [data, setData] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [month, setMonth] = useState(new Date().getMonth() + 1);
  const [year, setYear] = useState(new Date().getFullYear());

  useEffect(() => {
    setLoading(true);
    api.get('/user/riwayat-absen', { params: { month, year } })
      .then((r) => setData(r.data.data))
      .finally(() => setLoading(false));
  }, [month, year]);

  return (
    <div className="space-y-4">
      <div className="glass-card p-5">
        <h2 className="font-bold text-xl text-white flex items-center gap-2"><Calendar className="h-5 w-5 text-brand-400" /> Riwayat Presensi</h2>
        <div className="flex gap-2 mt-3">
          <select value={month} onChange={(e) => setMonth(+e.target.value)} className="input flex-1">
            {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
              <option key={m} value={m}>{new Date(2000, m - 1).toLocaleString('id-ID', { month: 'long' })}</option>
            ))}
          </select>
          <select value={year} onChange={(e) => setYear(+e.target.value)} className="input flex-1">
            {[2024, 2025, 2026, 2027].map((y) => <option key={y} value={y}>{y}</option>)}
          </select>
        </div>
      </div>

      {loading ? <div className="space-y-2">{[1, 2, 3].map((i) => <div key={i} className="h-20 skeleton rounded-xl" />)}</div> :
        data.length === 0 ? <div className="glass-card p-8 text-center text-neutral-500">Belum ada data presensi</div> :
        data.map((d) => (
          <div key={d.date} className="glass-card p-4 flex items-center justify-between">
            <div>
              <p className="font-semibold text-white">{formatDate(d.date, { weekday: 'short', day: '2-digit', month: 'short' })}</p>
              <div className="flex gap-3 mt-1.5 text-xs">
                <span className="text-emerald-400 flex items-center gap-1"><Clock className="h-3 w-3" /> Masuk: {d.check_in?.time ?? '-'}</span>
                <span className="text-brand-400 flex items-center gap-1"><Clock className="h-3 w-3" /> Pulang: {d.check_out?.time ?? '-'}</span>
              </div>
            </div>
            <span className={`badge ${d.status === 'late' ? 'badge-warning' : d.status === 'absent' ? 'badge-danger' : 'badge-success'}`}>
              {d.status === 'late' ? 'Terlambat' : d.status === 'absent' ? 'Tidak Hadir' : 'Tepat Waktu'}
            </span>
          </div>
        ))
      }
    </div>
  );
}
