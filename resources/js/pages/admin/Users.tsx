import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Plus, Search, Edit, Trash2, ScanFace, Shield, User } from 'lucide-react';
import api from '../../lib/axios';

export default function AdminUsers() {
  const [data, setData] = useState<any>({ data: [] });
  const [search, setSearch] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [edit, setEdit] = useState<any>(null);
  const [form, setForm] = useState<any>({ name: '', email: '', password: '', role: 'user', position: '', phone: '', whatsapp_number: '', telegram_chat_id: '', nik: '' });

  useEffect(() => { load(); }, [search]);
  const load = () => api.get('/admin/users', { params: { q: search } }).then((r) => setData(r.data));

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      if (edit) await api.put(`/admin/users/${edit.id}`, form);
      else await api.post('/admin/users', form);
      toast.success('Tersimpan. Notif Telegram dikirim ke admin.');
      setShowForm(false); setEdit(null);
      setForm({ name: '', email: '', password: '', role: 'user', position: '', phone: '', whatsapp_number: '', telegram_chat_id: '', nik: '' });
      load();
    } catch (e: any) { toast.error(e.response?.data?.message ?? 'Gagal'); }
  };

  const del = async (id: number) => {
    if (!confirm('Hapus user?')) return;
    await api.delete(`/admin/users/${id}`);
    toast.success('User dihapus');
    load();
  };

  const startEdit = (u: any) => {
    setEdit(u);
    setForm({ name: u.name, email: u.email, password: '', role: u.role, position: u.position ?? '', phone: u.phone ?? '', whatsapp_number: u.whatsapp_number ?? '', telegram_chat_id: u.telegram_chat_id ?? '', nik: u.nik ?? '' });
    setShowForm(true);
  };

  return (
    <div className="space-y-4">
      <div className="glass-card p-5 flex flex-wrap items-center justify-between gap-3">
        <h2 className="font-bold text-xl text-white">Manajemen User</h2>
        <button onClick={() => { setShowForm(true); setEdit(null); }} className="btn-primary !py-2 text-sm">
          <Plus className="h-4 w-4 inline mr-1" />Tambah User
        </button>
      </div>

      <div className="glass-card p-3">
        <div className="relative">
          <Search className="h-4 w-4 absolute left-3 top-3 text-brand-300/60" />
          <input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cari nama / email / NIK..." className="input pl-10" />
        </div>
      </div>

      {showForm && (
        <form onSubmit={submit} className="glass-card p-5 space-y-3">
          <h3 className="font-bold text-white">{edit ? 'Edit' : 'Tambah'} User</h3>
          <details className="text-xs text-brand-200/70 bg-brand-500/5 border border-brand-400/15 rounded-xl p-3">
            <summary className="cursor-pointer font-semibold text-brand-100">📲 Cara dapet Telegram Chat ID</summary>
            <ol className="mt-2 ml-4 space-y-1 list-decimal">
              <li>Suruh user buka Telegram → search <b>@userinfobot</b></li>
              <li>Klik <b>Start</b> di bot itu</li>
              <li>Bot akan kirim info user, copy angka di kolom <b>Id</b> (cth: <code>1234567890</code>)</li>
              <li>Paste angka itu di field "Telegram Chat ID" bawah</li>
              <li>Suruh user juga klik Start di bot lu (yg dikasih @BotFather) — wajib biar bisa nerima notif</li>
            </ol>
          </details>
          <div className="grid grid-cols-2 gap-3">
            <input required placeholder="Nama" value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} className="input" />
            <input required type="email" placeholder="Email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} className="input" />
            <input placeholder="NIK" value={form.nik} onChange={(e) => setForm({ ...form, nik: e.target.value })} className="input" />
            <input placeholder="Posisi" value={form.position} onChange={(e) => setForm({ ...form, position: e.target.value })} className="input" />
            <input placeholder="Telepon" value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} className="input" />
            <input placeholder="No. WhatsApp (628xxx, optional)" value={form.whatsapp_number} onChange={(e) => setForm({ ...form, whatsapp_number: e.target.value })} className="input" />
            <input placeholder="Telegram Chat ID (cara: chat @userinfobot)" value={form.telegram_chat_id} onChange={(e) => setForm({ ...form, telegram_chat_id: e.target.value })} className="input" title="Suruh user buka @userinfobot di Telegram → klik Start → copy 'Id' yang dikasih bot → paste di sini" />
            <input type="password" placeholder={edit ? 'Password (kosongkan jika tidak ganti)' : 'Password'} value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} className="input" />
            <select value={form.role} onChange={(e) => setForm({ ...form, role: e.target.value })} className="input">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div className="flex gap-2">
            <button type="submit" className="btn-primary flex-1">{edit ? 'Update' : 'Simpan'}</button>
            <button type="button" onClick={() => { setShowForm(false); setEdit(null); }} className="btn-ghost">Batal</button>
          </div>
        </form>
      )}

      <div className="glass-card p-1">
        <div className="divide-y divide-brand-400/10">
          {(data.data ?? []).map((u: any) => (
            <div key={u.id} className="p-4 t-row flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="h-10 w-10 rounded-xl bg-brand-gradient text-white flex items-center justify-center text-xs font-bold">{u.name.split(' ').map((n: string) => n[0]).slice(0, 2).join('')}</div>
                <div>
                  <p className="font-semibold text-white text-sm">{u.name}</p>
                  <p className="text-xs text-brand-200/70">{u.email} • {u.position ?? '-'}</p>
                  <div className="flex gap-1 mt-1">
                    <span className={`badge text-[9px] ${u.role === 'admin' ? 'badge-info' : ''}`}>{u.role === 'admin' ? <Shield className="h-2.5 w-2.5" /> : <User className="h-2.5 w-2.5" />} {u.role}</span>
                    {u.face_registered && <span className="badge badge-success text-[9px]"><ScanFace className="h-2.5 w-2.5" /> Face</span>}
                  </div>
                </div>
              </div>
              <div className="flex gap-1">
                <button onClick={() => startEdit(u)} className="btn-ghost p-2"><Edit className="h-3.5 w-3.5" /></button>
                <button onClick={() => del(u.id)} className="btn-ghost p-2 text-rose-300"><Trash2 className="h-3.5 w-3.5" /></button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
