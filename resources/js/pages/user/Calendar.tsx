import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { Calendar, MapPin, Users, Mic, ShieldCheck, CheckCircle2 } from 'lucide-react';
import api from '../../lib/axios';
import { formatDateTime } from '../../lib/utils';

export default function UserCalendar() {
  const [events, setEvents] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => { load(); }, []);
  const load = () => {
    setLoading(true);
    api.get('/user/calendar', { params: { month: new Date().getMonth() + 1, year: new Date().getFullYear() } })
      .then((r) => setEvents(r.data.events))
      .finally(() => setLoading(false));
  };

  const confirm = async (id: number) => {
    try {
      await api.post(`/user/calendar/${id}/confirm`);
      toast.success('Konfirmasi kehadiran berhasil dikirim');
      load();
    } catch (e: any) {
      toast.error(e.response?.data?.message ?? 'Gagal melakukan konfirmasi');
    }
  };

  return (
    <div className="space-y-4">
      <div className="glass-card p-5">
        <h2 className="font-bold text-xl text-white flex items-center gap-2">
          <Calendar className="h-5 w-5 text-brand-400" /> Kalender Saya
        </h2>
        <p className="text-xs text-neutral-400 mt-1">Acara publik & acara di mana Anda menjadi peserta</p>
      </div>

      {loading ? (
        <div className="space-y-3">{[1, 2, 3].map((i) => <div key={i} className="h-32 skeleton rounded-2xl" />)}</div>
      ) : events.length === 0 ? (
        <div className="glass-card p-8 text-center text-neutral-500">Tidak ada acara bulan ini</div>
      ) : (
        events.map((e) => (
          <article key={e.id} className="glass-card p-5 space-y-3">
            <header className="flex items-start justify-between gap-3">
              <div className="flex-1">
                <h3 className="font-bold text-white text-lg">{e.title}</h3>
                <p className="text-xs text-brand-200/70 mt-1 flex items-center gap-2">
                  <Calendar className="h-3 w-3" /> {formatDateTime(e.event_at)}
                </p>
                {e.location && (
                  <p className="text-xs text-brand-200/70 mt-1 flex items-center gap-2">
                    <MapPin className="h-3 w-3" /> {e.location}
                  </p>
                )}
              </div>
              <span className="badge badge-info uppercase">{e.category}</span>
            </header>

            {e.description && <p className="text-sm text-brand-100/80">{e.description}</p>}

            {/* Coordinators */}
            {e.coordinators?.length > 0 && (
              <div>
                <p className="text-[10px] uppercase tracking-wider text-brand-300/70 font-semibold flex items-center gap-1 mb-2">
                  <ShieldCheck className="h-3 w-3" /> Penanggung Jawab
                </p>
                <div className="flex flex-wrap gap-2">
                  {e.coordinators.map((c: any) => (
                    <span key={c.id} className="badge">{c.name} • {c.role}</span>
                  ))}
                </div>
              </div>
            )}

            {/* Speakers */}
            {e.speakers?.length > 0 && (
              <div>
                <p className="text-[10px] uppercase tracking-wider text-brand-300/70 font-semibold flex items-center gap-1 mb-2">
                  <Mic className="h-3 w-3" /> Pembicara
                </p>
                <div className="space-y-1.5">
                  {e.speakers.map((s: any) => (
                    <div key={s.id} className="text-sm">
                      <span className="font-semibold text-white">{s.name}</span>
                      {s.title && <span className="text-brand-200/70"> • {s.title}</span>}
                      {s.topic && <p className="text-xs text-brand-200/60 mt-0.5">📝 {s.topic}</p>}
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* My participation */}
            {e.my_participation && (
              <div className="pt-3 border-t border-brand-400/10 flex items-center justify-between">
                <span className={`badge ${e.my_participation.status === 'confirmed' ? 'badge-success' : 'badge-warning'}`}>
                  <Users className="h-3 w-3" /> Status: {e.my_participation.status}
                </span>
                {e.my_participation.status === 'invited' && (
                  <button onClick={() => confirm(e.id)} className="btn-primary !py-1.5 !px-3 text-xs">
                    <CheckCircle2 className="h-3 w-3 inline mr-1" /> Konfirmasi Hadir
                  </button>
                )}
              </div>
            )}
          </article>
        ))
      )}
    </div>
  );
}
