import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Plus, ChevronDown, TrendingUp, TrendingDown, Wallet } from 'lucide-react';
import api from '../../lib/axios';
import { formatRupiah, formatDate } from '../../lib/utils';

interface Cat { id: number; name: string; type: 'income' | 'expense'; icon?: string; color?: string }

export default function UserCashFlow() {
  const [rows, setRows] = useState<any[]>([]);
  const [cats, setCats] = useState<Cat[]>([]);
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ description: '', amount: '', type: 'expense', category_id: '', date: new Date().toISOString().substring(0, 10) });
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => { load(); api.get('/cash-categories').then(r => setCats(r.data)); }, []);
  const load = () => api.get('/user/cash-flow').then((r) => setRows(r.data.data ?? []));

  const filteredCats = cats.filter(c => c.type === form.type);

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      await api.post('/user/cash-flow', { ...form, amount: parseFloat(form.amount), category_id: form.category_id || null });
      toast.success('Transaksi berhasil dicatat. Menunggu persetujuan admin.');
      setForm({ description: '', amount: '', type: 'expense', category_id: '', date: new Date().toISOString().substring(0, 10) });
      setShowForm(false);
      load();
    } catch (e: any) {
      toast.error(e.response?.data?.message ?? 'Gagal menyimpan');
    } finally { setSubmitting(false); }
  };

  return (
    <div className="space-y-4">
      <div className="glass-card p-5 flex items-center justify-between">
        <div>
          <h2 className="font-bold text-xl text-white flex items-center gap-2">
            <Wallet className="h-5 w-5 text-brand-400" /> Alur Kas Saya
          </h2>
          <p className="text-xs text-neutral-400 mt-1">Pengajuan akan disetujui oleh admin</p>
        </div>
        <button onClick={() => setShowForm(!showForm)} className="btn-primary !py-2 !px-4 text-sm">
          <Plus className="h-4 w-4 inline mr-1" />Tambah
        </button>
      </div>

      {showForm && (
        <form onSubmit={submit} className="glass-card p-5 space-y-3">
          <div>
            <label className="text-xs font-semibold text-brand-200 uppercase mb-1.5 block">Tipe</label>
            <div className="grid grid-cols-2 gap-2">
              {(['income', 'expense'] as const).map((t) => (
                <button
                  type="button"
                  key={t}
                  onClick={() => setForm({ ...form, type: t, category_id: '' })}
                  className={`p-3 rounded-xl border-2 transition flex items-center justify-center gap-2 ${
                    form.type === t
                      ? t === 'income' ? 'border-emerald-400 bg-emerald-500/15 text-emerald-200' : 'border-rose-400 bg-rose-500/15 text-rose-200'
                      : 'border-brand-400/15 bg-surface-200/30 text-brand-300/70'
                  }`}
                >
                  {t === 'income' ? <TrendingUp className="h-4 w-4" /> : <TrendingDown className="h-4 w-4" />}
                  <span className="font-semibold text-sm">{t === 'income' ? 'Pemasukan' : 'Pengeluaran'}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Category Dropdown */}
          <div>
            <label className="text-xs font-semibold text-brand-200 uppercase mb-1.5 flex items-center gap-1">
              Kategori <ChevronDown className="h-3 w-3" />
            </label>
            <select
              required
              value={form.category_id}
              onChange={(e) => setForm({ ...form, category_id: e.target.value })}
              className="input"
            >
              <option value="">— Pilih Kategori —</option>
              {filteredCats.map((c) => (
                <option key={c.id} value={c.id}>{c.name}</option>
              ))}
            </select>
          </div>

          <div>
            <label className="text-xs font-semibold text-brand-200 uppercase mb-1.5 block">Deskripsi</label>
            <input required value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="input" />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-xs font-semibold text-brand-200 uppercase mb-1.5 block">Jumlah</label>
              <input type="number" min="0" required value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} className="input" />
            </div>
            <div>
              <label className="text-xs font-semibold text-brand-200 uppercase mb-1.5 block">Tanggal</label>
              <input type="date" required value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} className="input" />
            </div>
          </div>
          <button type="submit" disabled={submitting} className="btn-primary w-full">{submitting ? 'Mengirim...' : 'Simpan'}</button>
        </form>
      )}

      <div className="glass-card p-1">
        {rows.length === 0 ? (
          <p className="text-center py-8 text-neutral-500 text-sm">Belum ada transaksi</p>
        ) : (
          <div className="divide-y divide-brand-400/10">
            {rows.map((r: any) => (
              <div key={r.id} className="p-4 flex items-center justify-between t-row">
                <div className="flex items-center gap-3">
                  <div className={`h-10 w-10 rounded-xl ${r.type === 'income' ? 'bg-emerald-500/15' : 'bg-rose-500/15'} flex items-center justify-center`}>
                    {r.type === 'income' ? <TrendingUp className="h-5 w-5 text-emerald-300" /> : <TrendingDown className="h-5 w-5 text-rose-300" />}
                  </div>
                  <div>
                    <p className="font-semibold text-white text-sm">{r.description}</p>
                    <p className="text-[11px] text-brand-200/60">{r.category?.name ?? '-'} • {formatDate(r.date)}</p>
                  </div>
                </div>
                <div className="text-right">
                  <p className={`font-bold ${r.type === 'income' ? 'text-emerald-300' : 'text-rose-300'}`}>
                    {r.type === 'income' ? '+' : '-'}{formatRupiah(r.amount)}
                  </p>
                  <span className={`badge text-[9px] mt-0.5 ${r.status === 'approved' ? 'badge-success' : r.status === 'rejected' ? 'badge-danger' : 'badge-warning'}`}>{r.status}</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
