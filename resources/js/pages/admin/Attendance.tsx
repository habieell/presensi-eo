import { useEffect, useState } from 'react';
import { FileSpreadsheet, FileText, Filter } from 'lucide-react';
import { toast } from 'sonner';
import api from '../../lib/axios';
import { formatDate, downloadAuthed } from '../../lib/utils';

export default function AdminAttendance() {
  const [rows, setRows] = useState<any>({ data: [] });
  const [filter, setFilter] = useState({ month: new Date().getMonth() + 1, year: new Date().getFullYear() });

  useEffect(() => { api.get('/admin/attendance', { params: filter }).then((r) => setRows(r.data)); }, [filter]);

  const downloadCsv = async () => {
    try {
      await downloadAuthed(
        `/admin/attendance/export?month=${filter.month}&year=${filter.year}`,
        `attendance-${filter.year}-${String(filter.month).padStart(2, '0')}.csv`,
      );
      toast.success('CSV terdownload');
    } catch (e: any) {
      toast.error(e?.message ?? 'Gagal download');
    }
  };

  const downloadPdf = async () => {
    try {
      await downloadAuthed(
        `/admin/attendance/export-pdf?month=${filter.month}&year=${filter.year}`,
        `attendance-${filter.year}-${String(filter.month).padStart(2, '0')}.pdf`,
      );
      toast.success('PDF terdownload');
    } catch (e: any) {
      toast.error(e?.message ?? 'Gagal download');
    }
  };

  return (
    <div className="space-y-4">
      <div className="glass-card p-5 flex items-center justify-between flex-wrap gap-3">
        <h2 className="font-bold text-xl text-white">Data Absensi</h2>
        <div className="flex gap-2">
          <button onClick={downloadCsv} className="btn-ghost text-sm"><FileSpreadsheet className="h-4 w-4 inline mr-1" /> CSV</button>
          <button onClick={downloadPdf} className="btn-ghost text-sm"><FileText className="h-4 w-4 inline mr-1" /> PDF</button>
        </div>
      </div>

      <div className="glass-card p-3 flex gap-2">
        <select value={filter.month} onChange={(e) => setFilter({ ...filter, month: +e.target.value })} className="input flex-1">
          {Array.from({ length: 12 }, (_, i) => i + 1).map((m) => (
            <option key={m} value={m}>{new Date(2000, m - 1).toLocaleString('id-ID', { month: 'long' })}</option>
          ))}
        </select>
        <select value={filter.year} onChange={(e) => setFilter({ ...filter, year: +e.target.value })} className="input flex-1">
          {[2024, 2025, 2026, 2027].map((y) => <option key={y} value={y}>{y}</option>)}
        </select>
      </div>

      <div className="glass-card p-1 overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="text-xs text-brand-300/80 uppercase tracking-wider">
              <th className="text-left p-3">User</th>
              <th className="text-left p-3">Tanggal</th>
              <th className="text-left p-3">Tipe</th>
              <th className="text-left p-3">Jam</th>
              <th className="text-left p-3">Status</th>
              <th className="text-left p-3">Face</th>
            </tr>
          </thead>
          <tbody>
            {(rows.data ?? []).map((r: any) => (
              <tr key={r.id} className="t-row">
                <td className="p-3 text-white">{r.user?.name}</td>
                <td className="p-3 text-brand-200">{formatDate(r.date)}</td>
                <td className="p-3"><span className={`badge ${r.type === 'in' ? 'badge-success' : 'badge-info'}`}>{r.type.toUpperCase()}</span></td>
                <td className="p-3 text-brand-200 font-mono text-xs">{r.time}</td>
                <td className="p-3"><span className={`badge ${r.status === 'late' ? 'badge-warning' : 'badge-success'}`}>{r.status}</span></td>
                <td className="p-3">{r.face_verified ? <span className="badge badge-success text-[10px]">✓ {Math.round((r.face_score ?? 0) * 100)}%</span> : <span className="text-brand-300/40 text-xs">-</span>}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
