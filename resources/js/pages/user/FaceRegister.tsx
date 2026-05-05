import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { CheckCircle2, ScanFace, Trash2 } from 'lucide-react';
import api from '../../lib/axios';
import FaceCapture from '../../components/face/FaceCapture';
import { useAuth } from '../../contexts/AuthContext';

export default function FaceRegister() {
  const navigate = useNavigate();
  const { refresh } = useAuth();
  const [status, setStatus] = useState<{ registered: boolean; registered_at?: string; quality_score?: number } | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => { reload(); }, []);
  const reload = () => api.get('/user/face/status').then((r) => setStatus(r.data));

  const handleCapture = async (descriptor: number[], photo: Blob, quality: number) => {
    setSubmitting(true);
    try {
      const fd = new FormData();
      fd.append('photo', photo, 'face.jpg');
      descriptor.forEach((v, i) => fd.append(`descriptor[${i}]`, String(v)));
      fd.append('quality_score', String(quality));
      await api.post('/user/face/register', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      toast.success('✓ Wajah berhasil didaftarkan!');
      await Promise.all([reload(), refresh()]); // refresh auth context juga biar Profile/Dashboard update
      setTimeout(() => navigate('/dashboard'), 800);
    } catch (e: any) {
      toast.error(e.response?.data?.message ?? 'Gagal mendaftarkan wajah');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async () => {
    if (!confirm('Hapus pendaftaran wajah?')) return;
    await api.delete('/user/face/register');
    toast.success('Pendaftaran dihapus');
    await Promise.all([reload(), refresh()]);
  };

  return (
    <div className="space-y-4">
      <div className="glass-card p-5">
        <h2 className="font-bold text-xl text-white flex items-center gap-2">
          <ScanFace className="h-5 w-5 text-brand-400" /> Pendaftaran Wajah
        </h2>
        <p className="text-sm text-neutral-400 mt-1">Pendaftaran ini akan digunakan untuk verifikasi check-in dan check-out.</p>
      </div>

      {status?.registered && (
        <div className="glass-card p-4 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <CheckCircle2 className="h-6 w-6 text-emerald-400" />
            <div>
              <p className="font-semibold text-white text-sm">Wajah Sudah Terdaftar</p>
              <p className="text-xs text-neutral-400">
                {status.registered_at && new Date(status.registered_at).toLocaleString('id-ID')}
                {status.quality_score && ` • Kualitas: ${Math.round(status.quality_score * 100)}%`}
              </p>
            </div>
          </div>
          <button onClick={handleDelete} className="btn-ghost p-2 text-rose-300" title="Hapus">
            <Trash2 className="h-4 w-4" />
          </button>
        </div>
      )}

      <FaceCapture
        onCapture={handleCapture}
        buttonLabel={submitting ? 'Mendaftarkan...' : status?.registered ? 'Update Pendaftaran' : 'Daftarkan Wajah Sekarang'}
      />

      <div className="glass-card p-4 text-xs text-neutral-300 space-y-1.5">
        <p className="font-semibold text-brand-400 mb-1">Tips untuk hasil terbaik:</p>
        <p>• Pastikan pencahayaan cukup dan wajah terlihat jelas</p>
        <p>• Lepaskan masker dan kacamata gelap</p>
        <p>• Lihat lurus ke arah kamera</p>
        <p>• Jangan terlalu jauh atau terlalu dekat dari kamera</p>
      </div>
    </div>
  );
}
