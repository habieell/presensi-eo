import { useEffect, useState, FormEvent } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import { Eye, EyeOff, RefreshCcw, Sparkles, ShieldCheck } from 'lucide-react';
import api from '../../lib/axios';
import { useAuth } from '../../contexts/AuthContext';

interface Captcha { id: string; image: string; expires_in: number }

export default function Login() {
  const { login } = useAuth();
  const navigate = useNavigate();

  const [email, setEmail] = useState('admin@presensi.test');
  const [password, setPassword] = useState('admin123');
  const [showPwd, setShowPwd] = useState(false);
  const [captcha, setCaptcha] = useState<Captcha | null>(null);
  const [captchaAnswer, setCaptchaAnswer] = useState('');
  const [loading, setLoading] = useState(false);
  const [refreshingCaptcha, setRefreshingCaptcha] = useState(false);

  const loadCaptcha = async () => {
    setRefreshingCaptcha(true);
    try {
      const r = await api.get<Captcha>('/auth/captcha');
      setCaptcha(r.data);
      setCaptchaAnswer('');
    } catch (e) {
      toast.error('Gagal memuat captcha');
    } finally {
      setRefreshingCaptcha(false);
    }
  };

  useEffect(() => { loadCaptcha(); }, []);

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setLoading(true);
    try {
      const r = await api.post('/auth/login', {
        email,
        password,
        captcha_id: captcha?.id,
        captcha_answer: captchaAnswer,
      });
      login(r.data.token, r.data.user);
      toast.success('Login berhasil!');
      navigate(r.data.user.role === 'admin' ? '/admin' : '/dashboard');
    } catch (err: any) {
      const msg = err.response?.data?.message ?? 'Login gagal';
      toast.error(msg);
      if (err.response?.data?.captcha_failed) loadCaptcha();
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center px-4 py-12 relative overflow-hidden">
      {/* Decorative blobs */}
      <div className="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-brand-500/15 blur-3xl animate-float" />
      <div className="absolute -bottom-40 -right-40 h-96 w-96 rounded-full bg-brand-600/10 blur-3xl animate-float" style={{ animationDelay: '1.5s' }} />

      <div className="relative w-full max-w-md">
        {/* Logo */}
        <div className="flex flex-col items-center mb-8">
          <div className="h-16 w-16 rounded-2xl bg-brand-gradient-vivid flex items-center justify-center shadow-glow animate-pulse-glow mb-4">
            <Sparkles className="h-8 w-8 text-black" />
          </div>
          <h1 className="text-3xl font-bold text-gradient mb-1">Presensi</h1>
          <p className="text-sm text-neutral-400 font-medium">Sistem Presensi & Manajemen Modern</p>
        </div>

        {/* Card */}
        <div className="glass-card p-8">
          <h2 className="text-xl font-bold text-white mb-1">Selamat Datang Kembali</h2>
          <p className="text-sm text-neutral-400 mb-6">Masuk untuk mengakses dashboard Anda</p>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="text-xs font-semibold text-neutral-300 uppercase tracking-wider mb-1.5 block">Email</label>
              <input
                type="email"
                required
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="input"
                placeholder="email@example.com"
              />
            </div>

            <div>
              <label className="text-xs font-semibold text-neutral-300 uppercase tracking-wider mb-1.5 block">Password</label>
              <div className="relative">
                <input
                  type={showPwd ? 'text' : 'password'}
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="input pr-12"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  onClick={() => setShowPwd(!showPwd)}
                  className="absolute inset-y-0 right-0 px-4 text-neutral-500 hover:text-brand-400"
                >
                  {showPwd ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
              </div>
            </div>

            {/* Captcha */}
            <div>
              <label className="text-xs font-semibold text-neutral-300 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                <ShieldCheck className="h-3.5 w-3.5" /> Verifikasi Keamanan
              </label>
              <div className="flex items-center gap-2 mb-2">
                {captcha ? (
                  <img src={captcha.image} alt="captcha" className="h-14 rounded-lg border border-white/10" />
                ) : (
                  <div className="h-14 w-40 skeleton" />
                )}
                <button
                  type="button"
                  onClick={loadCaptcha}
                  disabled={refreshingCaptcha}
                  className="btn-ghost p-3"
                  title="Muat ulang captcha"
                >
                  <RefreshCcw className={`h-4 w-4 ${refreshingCaptcha ? 'animate-spin' : ''}`} />
                </button>
              </div>
              <input
                type="text"
                required
                value={captchaAnswer}
                onChange={(e) => setCaptchaAnswer(e.target.value)}
                className="input uppercase tracking-widest text-center"
                placeholder="Ketik kode di atas"
                autoComplete="off"
              />
            </div>

            <button type="submit" disabled={loading} className="btn-primary w-full">
              {loading ? 'Memproses...' : 'Masuk'}
            </button>
          </form>

          <div className="mt-6 pt-4 border-t border-white/5 text-xs text-neutral-500 text-center">
            <p className="mb-1">Akun Demo:</p>
            <p className="font-mono">admin@presensi.test / admin123</p>
            <p className="font-mono">user@presensi.test / user123</p>
          </div>
        </div>
      </div>
    </div>
  );
}
