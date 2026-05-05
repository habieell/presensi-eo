import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Plus, FileSpreadsheet, FileText, ChevronDown, TrendingUp, TrendingDown, Wallet, Check, X } from 'lucide-react';
import api from '../../lib/axios';
import { formatRupiah, formatDate, downloadAuthed } from '../../lib/utils';

export default function AdminCashFlow() {
  const [resp, setResp] = useState<any>({ data: { data: [] }, summary: {} });
  const [cats, setCats] = useState<any[]>([]);
  const [showForm, setShowForm] = useState(false);
  const [filter, setFilter] = useState({ type: '', category_id: '', status: '' });
  const [form, setForm] = useState({ category_id: '', description: '', amount: '', type: 'expense', date: new Date().toISOString().substring(0, 10), reference_no: '' });

  useEffect(() => { load(); api.get('/cash-categories').then((r) => setCats(r.data)); }, [filter]);
  const load = () => api.get('/admin/cash-flow', { params: filter }).then((r) => setResp(r.data));

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await api.post('/admin/cash-flow', { ...form, amount: parseFloat(form.amount), category_id: form.category_id || null });
      toast.success('Tersimpan. Notif WA terkirim ke admin.');
      setShowForm(false);
      setForm({ category_id: '', description: '', amount: '', type: 'expense', date: new Date().toISOString().substring(0, 10), reference_no: '' });
      load();
    } catch (e: any) { toast.error(e.response?.data?.message ?? 'Gagal'); }
  };

  const update = async (id: number, status: string) => {
    await api.put(`/admin/cash-flow/${id}`, { status });
    toast.success(`Status: ${status}`);
    load();
  };

  const del = async (id: number) => { if (!confirm('Hapus?')) return; await api.delete(`/admin/cash-flow/${id}`); load(); toast.success('Dihapus'); };

  const filteredCats = form.type ? cats.filter(c => c.type === form.type) : cats;

  return (
    <div className="space-y-4">
      <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div className="glass-card p-4">
          <p className="text-[10px] uppercase tracking-wider text-emerald-300/80 font-bold">Pemasukan</p>
          <p className="text-2xl font-bold text-emerald-300 mt-1">{formatRupiah(resp.summary?.total_income ?? 0)}</p>
        </div>
        <div className="glass-card p-4">
          <p className="text-[10px] uppercase tracking-wider text-rose-300/80 font-bold">Pengeluaran</p>
          <p className="text-2xl font-bold text-rose-300 mt-1">{formatRupiah(resp.summary?.total_expense ?? 0)}</p>
        </div>
        <div className="glass-card p-4">
          <p className="text-[10px] uppercase tracking-wider text-brand-300/80 font-bold">Saldo</p>
          <p className="text-2xl font-bold text-gradient mt-1">{formatRupiah(resp.summary?.balance ?? 0)}</p>
        </div>
      </div>

      <div className="glass-card p-3 flex flex-wrap gap-2">
        <select value={filter.type} onChange={(e) => setFilter({ ...filter, type: e.target.value })} className="input flex-1 min-w-[120px]">
          <option value="">Semua Tipe</option>
          <option value="income">Pemasukan</option>
          <option value="expense">Pengeluaran</option>
        </select>
        <select value={filter.category_id} onChange={(e) => setFilter({ ...filter, category_id: e.target.value })} className="input flex-1 min-w-[140px]">
          <option value="">Semua Kategori</option>
          {cats.map((c) => <option key={c.id} value={c.id}>{c.name} ({c.type})</option>)}
        </select>
        <select value={filter.status} onChange={(e) => setFilter({ ...filter, status: e.target.value })} className="input flex-1 min-w-[120px]">
          <option value="">Semua Status</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
        <button onClick={() => setShowForm(true)} className="btn-primary !py-2"><Plus className="h-4 w-4" /></button>
        <button onClick={async () => {
          try { await downloadAuthed('/admin/cash-flow/export', `cashflow-${new Date().toISOString().slice(0,10)}.csv`); toast.success('CSV terdownload'); }
          catch (e: any) { toast.error(e?.message ?? 'Gagal'); }
        }} className="btn-ghost !py-2" title="Export CSV"><FileSpreadsheet className="h-4 w-4" /></button>
        <button onClick={async () => {
          try { await downloadAuthed('/admin/cash-flow/export-pdf', `cashflow-${new Date().toISOString().slice(0,10)}.pdf`); toast.success('PDF terdownload'); }
          catch (e: any) { toast.error(e?.message ?? 'Gagal'); }
        }} className="btn-ghost !py-2" title="Export PDF"><FileText className="h-4 w-4" /></button>
      </div>

      {showForm && (
        <form onSubmit={submit} className="glass-card p-5 space-y-3">
          <h3 className="font-bold text-white flex items-center gap-2"><Wallet className="h-4 w-4 text-brand-300" /> Tambah Transaksi</h3>
          <div className="grid grid-cols-2 gap-2">
            {(['income', 'expense'] as const).map((t) => (
              <button type="button" key={t} onClick={() => setForm({ ...form, type: t, category_id: '' })}
                className={`p-3 rounded-xl border-2 transition flex items-center justify-center gap-2 ${
                  form.type === t
                    ? t === 'income' ? 'border-emerald-400 bg-emerald-500/15 text-emerald-200' : 'border-rose-400 bg-rose-500/15 text-rose-200'
                    : 'border-brand-400/15 bg-surface-200/30 text-brand-300/70'
                }`}>
                {t === 'income' ? <TrendingUp className="h-4 w-4" /> : <TrendingDown className="h-4 w-4" />}
                <span className="font-semibold text-sm">{t === 'income' ? 'Pemasukan' : 'Pengeluaran'}</span>
              </button>
            ))}
          </div>

          {/* DROPDOWN KATEGORI */}
          <div>
            <label className="text-xs font-semibold text-brand-200 uppercase mb-1.5 flex items-center gap-1">Kategori <ChevronDown className="h-3 w-3" /></label>
            <select required value={form.category_id} onChange={(e) => setForm({ ...form, category_id: e.target.value })} className="input">
              <option value="">— Pilih Kategori —</option>
              {filteredCats.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
          </div>

          <input required placeholder="Deskripsi" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="input" />
          <div className="grid grid-cols-2 gap-3">
            <input required type="number" placeholder="Jumlah" value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} className="input" />
            <input required type="date" value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} className="input" />
          </div>
          <input placeholder="No. Referensi (opsional)" value={form.reference_no} onChange={(e) => setForm({ ...form, reference_no: e.target.value })} className="input" />

          <div className="flex gap-2">
            <button type="submit" className="btn-primary flex-1">Simpan</button>
            <button type="button" onClick={() => setShowForm(false)} className="btn-ghost">Batal</button>
          </div>
        </form>
      )}

      <div className="glass-card p-1">
        <div className="divide-y divide-brand-400/10">
          {(resp.data?.data ?? []).map((r: any) => (
            <div key={r.id} className="p-4 flex items-center justify-between gap-3">
              <div className="flex items-center gap-3 flex-1 min-w-0">
                <div className={`h-10 w-10 rounded-xl ${r.type === 'income' ? 'bg-emerald-500/15' : 'bg-rose-500/15'} flex items-center justify-center shrink-0`}>
                  {r.type === 'income' ? <TrendingUp className="h-5 w-5 text-emerald-300" /> : <TrendingDown className="h-5 w-5 text-rose-300" />}
                </div>
                <div className="min-w-0">
                  <p className="font-semibold text-white text-sm truncate">{r.description}</p>
                  <p className="text-[11px] text-brand-200/60 truncate">{r.category?.name ?? '-'} • {formatDate(r.date)} • {r.creator?.name}</p>
                </div>
              </div>
              <div className="text-right">
                <p className={`font-bold ${r.type === 'income' ? 'text-emerald-300' : 'text-rose-300'} text-sm`}>{r.type === 'income' ? '+' : '-'}{formatRupiah(r.amount)}</p>
                <span className={`badge text-[9px] ${r.status === 'approved' ? 'badge-success' : r.status === 'rejected' ? 'badge-danger' : 'badge-warning'}`}>{r.status}</span>
              </div>
              {r.status === 'pending' && (
                <div className="flex gap-1">
                  <button onClick={() => update(r.id, 'approved')} className="btn-ghost p-2 text-emerald-300"><Check className="h-3.5 w-3.5" /></button>
                  <button onClick={() => update(r.id, 'rejected')} className="btn-ghost p-2 text-rose-300"><X className="h-3.5 w-3.5" /></button>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
