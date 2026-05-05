import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Plus, Trash2, Mic, ShieldCheck, Users, Calendar, MapPin } from 'lucide-react';
import api from '../../lib/axios';
import { formatDateTime } from '../../lib/utils';

const CATEGORIES = ['meeting', 'training', 'workshop', 'seminar', 'event', 'other'];
const VISIBILITIES = ['public', 'participants_only', 'private'];

export default function AdminEvents() {
  const [events, setEvents] = useState<any>({ data: [] });
  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState<any>({
    title: '', description: '', location: '', event_at: '', event_end_at: '',
    notify_before: 30, category: 'event', visibility: 'public', color: '#7c3aed',
    coordinators: [], speakers: [], participants: [],
  });

  useEffect(() => { load(); }, []);
  const load = () => api.get('/admin/events').then((r) => setEvents(r.data));

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await api.post('/admin/events', form);
      toast.success('Event terbuat. Reminder otomatis terjadwal.');
      setShowForm(false);
      setForm({ title: '', description: '', location: '', event_at: '', event_end_at: '', notify_before: 30, category: 'event', visibility: 'public', color: '#7c3aed', coordinators: [], speakers: [], participants: [] });
      load();
    } catch (e: any) { toast.error(e.response?.data?.message ?? 'Gagal'); }
  };

  const del = async (id: number) => { if (!confirm('Hapus event?')) return; await api.delete(`/admin/events/${id}`); load(); };

  // Helpers untuk nested arrays
  const addRow = (key: 'coordinators' | 'speakers' | 'participants', defaults: any) => {
    setForm({ ...form, [key]: [...form[key], defaults] });
  };
  const updateRow = (key: string, idx: number, field: string, value: any) => {
    const arr = [...form[key]];
    arr[idx] = { ...arr[idx], [field]: value };
    setForm({ ...form, [key]: arr });
  };
  const removeRow = (key: string, idx: number) => {
    const arr = [...form[key]];
    arr.splice(idx, 1);
    setForm({ ...form, [key]: arr });
  };

  return (
    <div className="space-y-4">
      <div className="glass-card p-5 flex items-center justify-between flex-wrap gap-3">
        <h2 className="font-bold text-xl text-white">Manajemen Event</h2>
        <button onClick={() => setShowForm(true)} className="btn-primary !py-2 text-sm"><Plus className="h-4 w-4 inline mr-1" />Buat Event</button>
      </div>

      {showForm && (
        <form onSubmit={submit} className="glass-card p-5 space-y-4">
          <h3 className="font-bold text-white">Event Baru</h3>
          <input required placeholder="Judul Event" value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} className="input" />
          <textarea placeholder="Deskripsi" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} className="input min-h-[80px]" />
          <input placeholder="Lokasi" value={form.location} onChange={(e) => setForm({ ...form, location: e.target.value })} className="input" />
          <div className="grid grid-cols-2 gap-3">
            <input required type="datetime-local" value={form.event_at} onChange={(e) => setForm({ ...form, event_at: e.target.value })} className="input" />
            <input type="datetime-local" placeholder="Selesai (opsional)" value={form.event_end_at} onChange={(e) => setForm({ ...form, event_end_at: e.target.value })} className="input" />
          </div>
          <div className="grid grid-cols-3 gap-3">
            <select value={form.category} onChange={(e) => setForm({ ...form, category: e.target.value })} className="input">
              {CATEGORIES.map(c => <option key={c} value={c}>{c}</option>)}
            </select>
            <select value={form.visibility} onChange={(e) => setForm({ ...form, visibility: e.target.value })} className="input">
              {VISIBILITIES.map(v => <option key={v} value={v}>{v}</option>)}
            </select>
            <input type="number" min="0" value={form.notify_before} onChange={(e) => setForm({ ...form, notify_before: +e.target.value })} className="input" placeholder="Notif (menit)" />
          </div>

          {/* Coordinators (Penanggung Jawab) - one to many */}
          <NestedSection title="Penanggung Jawab" icon={<ShieldCheck className="h-4 w-4 text-amber-300" />}
            onAdd={() => addRow('coordinators', { name: '', role: 'Penanggung Jawab', phone: '', email: '' })}>
            {form.coordinators.map((c: any, i: number) => (
              <div key={i} className="grid grid-cols-2 gap-2 p-3 rounded-xl bg-surface-200/40 relative">
                <input placeholder="Nama" value={c.name} onChange={(e) => updateRow('coordinators', i, 'name', e.target.value)} className="input" />
                <input placeholder="Role" value={c.role} onChange={(e) => updateRow('coordinators', i, 'role', e.target.value)} className="input" />
                <input placeholder="Telepon" value={c.phone ?? ''} onChange={(e) => updateRow('coordinators', i, 'phone', e.target.value)} className="input" />
                <input placeholder="Email" value={c.email ?? ''} onChange={(e) => updateRow('coordinators', i, 'email', e.target.value)} className="input" />
                <button type="button" onClick={() => removeRow('coordinators', i)} className="absolute top-1 right-1 p-1 text-rose-300"><Trash2 className="h-3 w-3" /></button>
              </div>
            ))}
          </NestedSection>

          {/* Speakers (Pembicara) - one to many */}
          <NestedSection title="Pembicara" icon={<Mic className="h-4 w-4 text-cyan-300" />}
            onAdd={() => addRow('speakers', { name: '', title: '', topic: '', bio: '' })}>
            {form.speakers.map((s: any, i: number) => (
              <div key={i} className="grid grid-cols-2 gap-2 p-3 rounded-xl bg-surface-200/40 relative">
                <input placeholder="Nama" value={s.name} onChange={(e) => updateRow('speakers', i, 'name', e.target.value)} className="input" />
                <input placeholder="Gelar/Title" value={s.title ?? ''} onChange={(e) => updateRow('speakers', i, 'title', e.target.value)} className="input" />
                <input placeholder="Organisasi" value={s.organization ?? ''} onChange={(e) => updateRow('speakers', i, 'organization', e.target.value)} className="input" />
                <input placeholder="Topik / Materi" value={s.topic ?? ''} onChange={(e) => updateRow('speakers', i, 'topic', e.target.value)} className="input" />
                <button type="button" onClick={() => removeRow('speakers', i)} className="absolute top-1 right-1 p-1 text-rose-300"><Trash2 className="h-3 w-3" /></button>
              </div>
            ))}
          </NestedSection>

          {/* Participants (Peserta) - one to many */}
          <NestedSection title="Peserta" icon={<Users className="h-4 w-4 text-emerald-300" />}
            onAdd={() => addRow('participants', { name: '', email: '', phone: '', institution: '' })}>
            {form.participants.map((p: any, i: number) => (
              <div key={i} className="grid grid-cols-2 gap-2 p-3 rounded-xl bg-surface-200/40 relative">
                <input placeholder="Nama" value={p.name} onChange={(e) => updateRow('participants', i, 'name', e.target.value)} className="input" />
                <input placeholder="Email" value={p.email ?? ''} onChange={(e) => updateRow('participants', i, 'email', e.target.value)} className="input" />
                <input placeholder="Telepon (untuk WA)" value={p.phone ?? ''} onChange={(e) => updateRow('participants', i, 'phone', e.target.value)} className="input" />
                <input placeholder="Institusi" value={p.institution ?? ''} onChange={(e) => updateRow('participants', i, 'institution', e.target.value)} className="input" />
                <button type="button" onClick={() => removeRow('participants', i)} className="absolute top-1 right-1 p-1 text-rose-300"><Trash2 className="h-3 w-3" /></button>
              </div>
            ))}
          </NestedSection>

          <div className="flex gap-2">
            <button type="submit" className="btn-primary flex-1">Simpan Event</button>
            <button type="button" onClick={() => setShowForm(false)} className="btn-ghost">Batal</button>
          </div>
        </form>
      )}

      {/* Event list */}
      <div className="space-y-3">
        {(events.data ?? []).map((e: any) => (
          <article key={e.id} className="glass-card p-5 space-y-2">
            <header className="flex items-start justify-between">
              <div>
                <h3 className="font-bold text-white">{e.title}</h3>
                <p className="text-xs text-brand-200/70 mt-1 flex items-center gap-1.5"><Calendar className="h-3 w-3" /> {formatDateTime(e.event_at)}</p>
                {e.location && <p className="text-xs text-brand-200/70 mt-0.5 flex items-center gap-1.5"><MapPin className="h-3 w-3" /> {e.location}</p>}
              </div>
              <div className="flex flex-col items-end gap-1">
                <span className="badge badge-info text-[10px]">{e.category}</span>
                <button onClick={() => del(e.id)} className="btn-ghost p-1.5 text-rose-300"><Trash2 className="h-3 w-3" /></button>
              </div>
            </header>
            <div className="flex flex-wrap gap-3 pt-2 text-xs">
              <span className="text-amber-300 flex items-center gap-1"><ShieldCheck className="h-3 w-3" /> {e.coordinators?.length ?? 0} PJ</span>
              <span className="text-cyan-300 flex items-center gap-1"><Mic className="h-3 w-3" /> {e.speakers?.length ?? 0} Pembicara</span>
              <span className="text-emerald-300 flex items-center gap-1"><Users className="h-3 w-3" /> {e.participants_count ?? 0} Peserta</span>
            </div>
          </article>
        ))}
      </div>
    </div>
  );
}

function NestedSection({ title, icon, children, onAdd }: { title: string; icon: React.ReactNode; children: React.ReactNode; onAdd: () => void }) {
  return (
    <div className="space-y-2 p-3 rounded-xl border border-brand-400/15 bg-surface-100/30">
      <div className="flex items-center justify-between">
        <p className="text-xs font-bold text-brand-100 uppercase tracking-wider flex items-center gap-1.5">{icon} {title}</p>
        <button type="button" onClick={onAdd} className="btn-ghost !py-1 !px-2 text-xs"><Plus className="h-3 w-3 inline" /> Tambah</button>
      </div>
      <div className="space-y-2">{children}</div>
    </div>
  );
}
