import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { CheckCircle2, MapPin, LogIn, LogOut, Info } from 'lucide-react';
import FaceCapture from '../../components/face/FaceCapture';
import api from '../../lib/axios';

type Mode = 'in' | 'out';

export default function CheckIn() {
  const navigate = useNavigate();
  const [mode, setMode] = useState<Mode>('in');
  const [location, setLocation] = useState<{ lat?: number; lng?: number; label?: string }>({});
  const [submitting, setSubmitting] = useState(false);
  const [todayStatus, setTodayStatus] = useState<any>(null);
  const [faceRegistered, setFaceRegistered] = useState<boolean | null>(null);

  useEffect(() => {
    api.get('/user/dashboard').then((r) => {
      setTodayStatus(r.data.today);
      setFaceRegistered(r.data.face_registered);
      if (r.data.today.check_in && !r.data.today.check_out) setMode('out');
    });

    if ('geolocation' in navigator) {
      navigator.geolocation.getCurrentPosition(
        (pos) => setLocation({ lat: pos.coords.latitude, lng: pos.coords.longitude, label: `Lat: ${pos.coords.latitude.toFixed(4)}, Lng: ${pos.coords.longitude.toFixed(4)}` }),
        () => setLocation({ label: 'Lokasi tidak tersedia' }),
      );
    }
  }, []);

  const handleCapture = async (descriptor: number[], photo: Blob) => {
    setSubmitting(true);
    try {
      const fd = new FormData();
      fd.append('photo', photo, 'snapshot.jpg');
      fd.append('face_descriptor', JSON.stringify(descriptor));
      // axios needs raw array - send as JSON inside form
      descriptor.forEach((v, i) => fd.append(`face_descriptor[${i}]`, String(v)));
      if (location.label) fd.append('location', location.label);
      if (location.lat) fd.append('latitude', String(location.lat));
      if (location.lng) fd.append('longitude', String(location.lng));

      const url = mode === 'in' ? '/user/check-in' : '/user/check-out';
      await api.post(url, fd, { headers: { 'Content-Type': 'multipart/form-data' } });
      toast.success(`✓ ${mode === 'in' ? 'Check-in' : 'Check-out'} berhasil!`);
      setTimeout(() => navigate('/dashboard'), 800);
    } catch (e: any) {
      const msg = e.response?.data?.message ?? 'Gagal melakukan presensi';
      toast.error(msg);
    } finally {
      setSubmitting(false);
    }
  };

  if (faceRegistered === false) {
    return (
      <div className="glass-card p-8 text-center">
        <Info className="h-12 w-12 text-brand-400 mx-auto mb-3" />
        <h2 className="text-xl font-bold text-white mb-2">Daftarkan Wajah Terlebih Dahulu</h2>
        <p className="text-sm text-neutral-400 mb-4">Anda perlu mendaftarkan wajah sebelum dapat melakukan presensi.</p>
        <button onClick={() => navigate('/face-register')} className="btn-primary">Daftar Wajah Sekarang</button>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="glass-card p-5">
        <h2 className="font-bold text-xl text-white mb-1">Presensi Hari Ini</h2>
        <p className="text-sm text-neutral-400">Pilih mode lalu posisikan wajah di depan kamera</p>
      </div>

      {/* Mode toggle */}
      <div className="grid grid-cols-2 gap-2">
        <button
          onClick={() => setMode('in')}
          disabled={!!todayStatus?.check_in}
          className={`glass-card p-4 flex items-center gap-3 ${mode === 'in' ? 'ring-2 ring-brand-400/60' : ''} ${todayStatus?.check_in ? 'opacity-50 cursor-not-allowed' : ''}`}
        >
          <LogIn className="h-5 w-5 text-brand-400" />
          <div className="text-left">
            <p className="font-bold text-white">Check-In</p>
            <p className="text-[10px] text-neutral-500">{todayStatus?.check_in?.time ?? 'Belum tercatat'}</p>
          </div>
        </button>
        <button
          onClick={() => setMode('out')}
          disabled={!todayStatus?.check_in || !!todayStatus?.check_out}
          className={`glass-card p-4 flex items-center gap-3 ${mode === 'out' ? 'ring-2 ring-brand-400/60' : ''} ${(!todayStatus?.check_in || todayStatus?.check_out) ? 'opacity-50 cursor-not-allowed' : ''}`}
        >
          <LogOut className="h-5 w-5 text-brand-400" />
          <div className="text-left">
            <p className="font-bold text-white">Check-Out</p>
            <p className="text-[10px] text-neutral-500">{todayStatus?.check_out?.time ?? 'Belum tercatat'}</p>
          </div>
        </button>
      </div>

      <FaceCapture
        onCapture={handleCapture}
        buttonLabel={submitting ? 'Mengirim...' : `${mode === 'in' ? 'Check-In' : 'Check-Out'} Sekarang`}
      />

      {location.label && (
        <div className="glass-card p-3 flex items-center gap-2 text-xs text-neutral-300">
          <MapPin className="h-4 w-4 text-brand-400" />
          <span className="font-mono">{location.label}</span>
        </div>
      )}
    </div>
  );
}
