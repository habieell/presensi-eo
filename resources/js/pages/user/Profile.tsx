import { useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { motion, AnimatePresence } from 'framer-motion';
import {
  User, Mail, Phone, Briefcase, ScanFace, Save, Send,
  CheckCircle2, ExternalLink, Unlink, Loader2, Lock, MapPin, FileText,
  Pencil, X,
} from 'lucide-react';
import api from '../../lib/axios';
import { useAuth } from '../../contexts/AuthContext';

interface FormState {
  name: string;
  position: string;
  phone: string;
  whatsapp_number: string;
  address: string;
  bio: string;
  password: string;
  password_confirmation: string;
}

const fieldLabel = (field: string) => {
  const map: Record<string, string> = {
    name: 'Nama', position: 'Posisi', phone: 'Telepon',
    whatsapp_number: 'No. WhatsApp', address: 'Alamat', bio: 'Bio',
    password: 'Password',
  };
  return map[field] ?? field;
};

export default function UserProfile() {
  const { user, refresh } = useAuth();
  const [pending, setPending] = useState<any[]>([]);
  const [faceRegistered, setFaceRegistered] = useState<boolean>(!!user?.face_registered);
  const [profileData, setProfileData] = useState<any>(user);
  const [showEditModal, setShowEditModal] = useState(false);

  const reload = async () => {
    const r = await api.get('/user/profile');
    setProfileData(r.data.user);
    setPending(r.data.pending_requests ?? []);
  };

  useEffect(() => {
    reload();
    api.get('/user/face/status').then((r) => setFaceRegistered(!!r.data?.registered));
    refresh().catch(() => {});
  }, []);

  return (
    <div className="space-y-4">
      {/* Header card */}
      <div className="glass-card p-5 sm:p-6 relative">
        {/* Edit icon — pojok kanan atas */}
        <button
          onClick={() => setShowEditModal(true)}
          className="absolute top-3 right-3 h-9 w-9 rounded-xl bg-brand-500/15 border border-brand-400/30 hover:bg-brand-500/25 hover:border-brand-400/60 transition flex items-center justify-center text-brand-400"
          title="Edit profil"
          aria-label="Edit profil"
        >
          <Pencil className="h-4 w-4" />
        </button>

        {/* Avatar + name (centered di mobile, kiri di desktop) */}
        <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4 mb-5 sm:pr-12 text-center sm:text-left">
          <div className="h-20 w-20 sm:h-16 sm:w-16 rounded-2xl bg-brand-gradient-vivid flex items-center justify-center text-black font-bold text-2xl shadow-glow shrink-0">
            {profileData?.name?.split(' ').map((n: string) => n[0]).slice(0, 2).join('')}
          </div>
          <div className="min-w-0 flex-1 w-full">
            <h2 className="text-xl font-bold text-white break-words">{profileData?.name}</h2>
            <p className="text-sm text-neutral-400 break-all">{profileData?.email}</p>
            <div className="mt-2 flex flex-wrap gap-1.5 justify-center sm:justify-start">
              <span className={`badge ${faceRegistered ? 'badge-success' : 'badge-warning'}`}>
                <ScanFace className="h-3 w-3" />
                {faceRegistered ? 'Wajah Terdaftar' : 'Belum Mendaftar Wajah'}
              </span>
            </div>
          </div>
        </div>

        <div className="grid sm:grid-cols-2 gap-3">
          <Info icon={<User className="h-4 w-4" />} label="NIK" value={profileData?.nik ?? '-'} />
          <Info icon={<Briefcase className="h-4 w-4" />} label="Posisi" value={profileData?.position ?? '-'} />
          <Info icon={<Phone className="h-4 w-4" />} label="Telepon" value={profileData?.phone ?? '-'} />
          <Info icon={<Phone className="h-4 w-4" />} label="WhatsApp" value={profileData?.whatsapp_number ?? '-'} />
          <Info icon={<MapPin className="h-4 w-4" />} label="Alamat" value={profileData?.address ?? '-'} />
          <Info icon={<FileText className="h-4 w-4" />} label="Bio" value={profileData?.bio ?? '-'} />
        </div>
      </div>

      {/* TELEGRAM CONNECT */}
      <TelegramConnectCard />

      {/* Pending */}
      {pending.length > 0 && (
        <div className="glass-card p-6">
          <h3 className="font-bold text-white mb-3">Permintaan Tertunda ({pending.length})</h3>
          <div className="space-y-2">
            {pending.map((p) => (
              <div key={p.id} className="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20">
                <p className="text-sm text-white">
                  <b>{fieldLabel(p.field)}</b>: {p.field === 'password' ? '••••••••' : p.new_value}
                </p>
                <p className="text-xs text-amber-300/80 mt-1">Menunggu persetujuan admin</p>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* MODAL EDIT PROFILE */}
      <EditProfileModal
        open={showEditModal}
        onClose={() => setShowEditModal(false)}
        initial={profileData}
        onSubmitted={reload}
      />
    </div>
  );
}

// ─────────────────────────────────────────────────────────
// Edit Profile Modal
// ─────────────────────────────────────────────────────────
function EditProfileModal({
  open, onClose, initial, onSubmitted,
}: {
  open: boolean;
  onClose: () => void;
  initial: any;
  onSubmitted: () => void;
}) {
  const [form, setForm] = useState<FormState>({
    name: '', position: '', phone: '', whatsapp_number: '',
    address: '', bio: '', password: '', password_confirmation: '',
  });
  const initialRef = useRef<FormState | null>(null);
  const [submitting, setSubmitting] = useState(false);

  // Reset form tiap modal di-buka
  useEffect(() => {
    if (open && initial) {
      const init: FormState = {
        name: initial.name ?? '',
        position: initial.position ?? '',
        phone: initial.phone ?? '',
        whatsapp_number: initial.whatsapp_number ?? '',
        address: initial.address ?? '',
        bio: initial.bio ?? '',
        password: '',
        password_confirmation: '',
      };
      setForm(init);
      initialRef.current = init;
    }
  }, [open, initial]);

  // Prevent body scroll when modal open + handle ESC key
  useEffect(() => {
    if (!open) return;
    const original = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose(); };
    window.addEventListener('keydown', onKey);
    return () => {
      document.body.style.overflow = original;
      window.removeEventListener('keydown', onKey);
    };
  }, [open, onClose]);

  const update = (k: keyof FormState, v: string) => setForm((f) => ({ ...f, [k]: v }));

  const submit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!initialRef.current) return;

    if (form.password && form.password !== form.password_confirmation) {
      toast.error('Konfirmasi password tidak cocok');
      return;
    }

    const changed: { field: string; new_value: string }[] = [];
    (Object.keys(form) as (keyof FormState)[]).forEach((k) => {
      if (k === 'password_confirmation') return;
      if (k === 'password' && !form.password) return;
      if (form[k] !== (initialRef.current as any)[k]) {
        changed.push({ field: k, new_value: form[k] });
      }
    });

    if (changed.length === 0) {
      toast.info('Tidak ada perubahan untuk diajukan');
      return;
    }

    setSubmitting(true);
    try {
      for (const c of changed) {
        await api.post('/user/profile/request-update', c);
      }
      toast.success(`${changed.length} permintaan dikirim ke admin untuk persetujuan`);
      onSubmitted();
      onClose();
    } catch (e: any) {
      toast.error(e.response?.data?.message ?? 'Gagal mengirim permintaan');
    } finally { setSubmitting(false); }
  };

  return (
    <AnimatePresence>
      {open && (
        <motion.div
          className="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
        >
          {/* Overlay */}
          <motion.div
            className="absolute inset-0 bg-black/70 backdrop-blur-sm"
            onClick={onClose}
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.25 }}
          />

          {/* Modal panel */}
          <motion.div
            className="relative glass-strong w-full sm:max-w-2xl max-h-[92vh] overflow-y-auto rounded-t-3xl sm:rounded-3xl border border-brand-400/15 shadow-2xl"
            initial={{ y: '100%', opacity: 0, scale: 0.95 }}
            animate={{ y: 0, opacity: 1, scale: 1 }}
            exit={{ y: '100%', opacity: 0, scale: 0.95 }}
            transition={{ type: 'spring', stiffness: 320, damping: 32, mass: 0.6 }}
            onClick={(e) => e.stopPropagation()}
          >
            {/* Drag handle (mobile) */}
            <div className="sm:hidden flex justify-center pt-3">
              <div className="w-10 h-1 rounded-full bg-white/20" />
            </div>

            {/* Header sticky */}
            <div className="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-white/5 bg-black/60 backdrop-blur-md">
              <div>
                <h3 className="font-bold text-white text-lg">Edit Profil</h3>
                <p className="text-xs text-neutral-400 mt-0.5">Setiap perubahan akan dikirim ke admin untuk persetujuan</p>
              </div>
              <button onClick={onClose} className="btn-ghost p-2" aria-label="Tutup">
                <X className="h-4 w-4" />
              </button>
            </div>

            {/* Form */}
            <form onSubmit={submit} className="p-6 space-y-5">
              <div className="grid sm:grid-cols-2 gap-4">
                <Field label="Nama Lengkap" icon={<User className="h-3 w-3" />}>
                  <input className="input" value={form.name} onChange={(e) => update('name', e.target.value)} />
                </Field>

                <Field label="Posisi / Jabatan" icon={<Briefcase className="h-3 w-3" />}>
                  <input className="input" value={form.position} onChange={(e) => update('position', e.target.value)} />
                </Field>

                <Field label="No. Telepon" icon={<Phone className="h-3 w-3" />}>
                  <input className="input" value={form.phone} onChange={(e) => update('phone', e.target.value)} placeholder="08xx xxxx xxxx" />
                </Field>

                <Field label="No. WhatsApp" icon={<Phone className="h-3 w-3" />}>
                  <input className="input" value={form.whatsapp_number} onChange={(e) => update('whatsapp_number', e.target.value)} placeholder="628xx xxxx xxxx" />
                </Field>

                <Field label="Alamat" icon={<MapPin className="h-3 w-3" />} className="sm:col-span-2">
                  <textarea className="input min-h-[70px]" value={form.address} onChange={(e) => update('address', e.target.value)} />
                </Field>

                <Field label="Bio" icon={<FileText className="h-3 w-3" />} className="sm:col-span-2">
                  <textarea className="input min-h-[70px]" value={form.bio} onChange={(e) => update('bio', e.target.value)} placeholder="Sedikit tentang Anda..." />
                </Field>
              </div>

              {/* Password section */}
              <div className="pt-4 border-t border-white/5">
                <p className="text-xs uppercase tracking-wider text-brand-400 font-bold mb-3 flex items-center gap-1.5">
                  <Lock className="h-3 w-3" /> Ganti Password (opsional)
                </p>
                <div className="grid sm:grid-cols-2 gap-4">
                  <Field label="Password Baru">
                    <input type="password" className="input" value={form.password} onChange={(e) => update('password', e.target.value)} placeholder="Kosongkan jika tidak ingin mengubah" autoComplete="new-password" />
                  </Field>
                  <Field label="Konfirmasi Password">
                    <input type="password" className="input" value={form.password_confirmation} onChange={(e) => update('password_confirmation', e.target.value)} placeholder="Ulangi password baru" autoComplete="new-password" />
                  </Field>
                </div>
              </div>

              {/* Footer sticky */}
              <div className="sticky bottom-0 -mx-6 -mb-6 px-6 py-4 bg-black/70 backdrop-blur-md border-t border-white/5 flex gap-2">
                <button type="button" onClick={onClose} className="btn-ghost flex-1">
                  Batal
                </button>
                <button type="submit" disabled={submitting} className="btn-primary flex-[2]">
                  <Save className="h-4 w-4 inline mr-2" />
                  {submitting ? 'Mengirim...' : 'Ajukan Perubahan'}
                </button>
              </div>
            </form>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
}

// ─────────────────────────────────────────────────────────
// Telegram Self-Connect Card
// ─────────────────────────────────────────────────────────
function TelegramConnectCard() {
  const [status, setStatus] = useState<{ linked: boolean; chat_id?: string; username?: string; linked_at?: string; notification_enabled?: boolean } | null>(null);
  const [link, setLink] = useState<{ deep_link: string; bot_username: string; token: string } | null>(null);
  const [loading, setLoading] = useState(false);
  const [polling, setPolling] = useState(false);
  const pollingTimerRef = useRef<number | null>(null);

  const loadStatus = async () => {
    try {
      const r = await api.get('/user/telegram/status');
      setStatus(r.data);
      return r.data;
    } catch { return null; }
  };

  useEffect(() => {
    loadStatus();
    return () => { if (pollingTimerRef.current) window.clearInterval(pollingTimerRef.current); };
  }, []);

  const startConnect = async () => {
    setLoading(true);
    try {
      const r = await api.post('/user/telegram/link');
      setLink(r.data);
      window.open(r.data.deep_link, '_blank');
      setPolling(true);
      pollingTimerRef.current = window.setInterval(async () => {
        const s = await loadStatus();
        if (s?.linked) {
          window.clearInterval(pollingTimerRef.current!);
          setPolling(false);
          setLink(null);
          toast.success('Telegram berhasil terhubung');
        }
      }, 3000);
      setTimeout(() => {
        if (pollingTimerRef.current) {
          window.clearInterval(pollingTimerRef.current);
          setPolling(false);
        }
      }, 600000);
    } catch (e: any) {
      toast.error(e.response?.data?.message ?? 'Gagal membuat link');
    } finally { setLoading(false); }
  };

  const unlink = async () => {
    if (!confirm('Putuskan koneksi Telegram?')) return;
    await api.delete('/user/telegram/link');
    toast.success('Telegram berhasil diputuskan');
    loadStatus();
  };

  const toggleNotif = async () => {
    if (!status) return;
    await api.post('/user/telegram/toggle', { enabled: !status.notification_enabled });
    loadStatus();
  };

  return (
    <div className="glass-card p-6">
      <div className="flex items-center justify-between mb-3">
        <h3 className="font-bold text-white flex items-center gap-2">
          <Send className="h-4 w-4 text-brand-400" /> Notifikasi Telegram
        </h3>
        {status?.linked && (
          <span className="badge badge-success">
            <CheckCircle2 className="h-3 w-3" /> Terhubung
          </span>
        )}
      </div>

      {!status ? (
        <div className="h-16 skeleton rounded-xl" />
      ) : status.linked ? (
        <div className="space-y-3">
          <div className="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
            <p className="text-sm text-white">
              Terhubung sebagai <b>@{status.username ?? 'unknown'}</b>
            </p>
            <p className="text-xs text-emerald-300/80 mt-1">
              Chat ID: <code>{status.chat_id}</code>
              {status.linked_at && ` • ${new Date(status.linked_at).toLocaleString('id-ID')}`}
            </p>
          </div>
          <div className="flex gap-2">
            <button onClick={toggleNotif} className="btn-ghost flex-1 text-xs">
              {status.notification_enabled ? 'Notifikasi: AKTIF' : 'Notifikasi: NONAKTIF'}
            </button>
            <button onClick={unlink} className="btn-ghost text-rose-300 text-xs">
              <Unlink className="h-3 w-3 inline mr-1" /> Putuskan
            </button>
          </div>
        </div>
      ) : (
        <div className="space-y-3">
          <p className="text-sm text-neutral-400">
            Terima notifikasi check-in, pengingat acara, dan pemberitahuan penting langsung di Telegram. <b>Gratis & instan.</b>
          </p>

          {!link ? (
            <button onClick={startConnect} disabled={loading} className="btn-primary w-full">
              {loading ? <Loader2 className="h-4 w-4 inline mr-2 animate-spin" /> : <Send className="h-4 w-4 inline mr-2" />}
              {loading ? 'Membuat link...' : 'Hubungkan Telegram'}
            </button>
          ) : (
            <div className="space-y-2">
              <div className="p-4 rounded-xl bg-brand-500/10 border border-brand-400/30 text-sm space-y-2">
                <p className="text-brand-300 font-semibold flex items-center gap-2">
                  {polling && <Loader2 className="h-4 w-4 animate-spin" />}
                  {polling ? 'Menunggu Anda klik START di Telegram...' : 'Link sudah dibuat'}
                </p>
                <p className="text-xs text-neutral-400">
                  Tab Telegram seharusnya sudah terbuka. Klik tombol <b>START</b> di chat-nya, lalu kembali ke halaman ini.
                </p>
                <a href={link.deep_link} target="_blank" rel="noopener" className="btn-ghost text-xs inline-flex items-center gap-1 mt-1">
                  <ExternalLink className="h-3 w-3" /> Buka @{link.bot_username} lagi
                </a>
              </div>

              <details className="text-xs text-neutral-500">
                <summary className="cursor-pointer">Tab Telegram tidak terbuka?</summary>
                <div className="mt-2 p-2 bg-white/5 rounded-lg break-all">
                  <p>Salin link berikut & buka manual:</p>
                  <code className="text-brand-300">{link.deep_link}</code>
                </div>
              </details>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

// ─────────────────────────────────────────────────────────
function Info({ icon, label, value }: { icon: React.ReactNode; label: string; value: string }) {
  return (
    <div className="p-3 rounded-xl bg-white/[0.02] border border-white/5 flex items-start gap-3">
      <div className="text-brand-400 mt-0.5">{icon}</div>
      <div className="min-w-0 flex-1">
        <p className="text-[10px] uppercase tracking-wider text-neutral-500 font-semibold">{label}</p>
        <p className="text-sm text-white font-medium break-words">{value}</p>
      </div>
    </div>
  );
}

function Field({ label, icon, className, children }: { label: string; icon?: React.ReactNode; className?: string; children: React.ReactNode }) {
  return (
    <div className={className}>
      <label className="text-xs font-semibold text-neutral-300 uppercase mb-1.5 flex items-center gap-1.5">
        {icon} {label}
      </label>
      {children}
    </div>
  );
}
