import { useEffect, useRef, useState } from 'react';
import * as faceapi from 'face-api.js';
import { Camera, Loader2, ScanFace, AlertCircle, CheckCircle2 } from 'lucide-react';

interface FaceCaptureProps {
  /** Disebut tiap kali deteksi sukses dengan kualitas baik */
  onCapture: (descriptor: number[], snapshot: Blob, qualityScore: number) => void;
  /** Disebut tiap perubahan status deteksi (true = wajah terdeteksi) */
  onDetectionChange?: (detected: boolean) => void;
  /** Min confidence */
  minConfidence?: number;
  /** Auto-capture begitu kualitas cukup (default: false, button manual) */
  autoCapture?: boolean;
  /** label tombol */
  buttonLabel?: string;
}

const MODEL_URL = '/face-models';

export default function FaceCapture({
  onCapture,
  onDetectionChange,
  minConfidence = 0.7,
  autoCapture = false,
  buttonLabel = 'Ambil & Verifikasi Wajah',
}: FaceCaptureProps) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const streamRef = useRef<MediaStream | null>(null);
  const [status, setStatus] = useState<'loading' | 'ready' | 'error'>('loading');
  const [errorMsg, setErrorMsg] = useState('');
  const [detection, setDetection] = useState<faceapi.WithFaceDescriptor<faceapi.WithFaceLandmarks<{ detection: faceapi.FaceDetection }, faceapi.FaceLandmarks68>> | null>(null);
  const [capturing, setCapturing] = useState(false);

  useEffect(() => {
    let cancelled = false;

    const init = async () => {
      try {
        setStatus('loading');
        await Promise.all([
          faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
          faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
          faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
        ]);

        const stream = await navigator.mediaDevices.getUserMedia({
          video: { width: 480, height: 360, facingMode: 'user' },
          audio: false,
        });
        if (cancelled) {
          stream.getTracks().forEach(t => t.stop());
          return;
        }
        streamRef.current = stream;
        if (videoRef.current) {
          videoRef.current.srcObject = stream;
          await videoRef.current.play();
        }
        setStatus('ready');
        startDetectionLoop();
      } catch (e: any) {
        setErrorMsg(e?.message ?? 'Gagal mengakses kamera atau memuat model');
        setStatus('error');
      }
    };

    init();
    return () => {
      cancelled = true;
      streamRef.current?.getTracks().forEach(t => t.stop());
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const startDetectionLoop = () => {
    const detect = async () => {
      if (!videoRef.current || videoRef.current.readyState !== 4) {
        requestAnimationFrame(detect);
        return;
      }
      const result = await faceapi
        .detectSingleFace(videoRef.current, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: minConfidence }))
        .withFaceLandmarks()
        .withFaceDescriptor();

      if (result) {
        setDetection(result);
        onDetectionChange?.(true);
        drawOverlay(result);

        if (autoCapture && result.detection.score > 0.85) {
          await captureNow(result);
        }
      } else {
        setDetection(null);
        onDetectionChange?.(false);
        clearOverlay();
      }

      requestAnimationFrame(detect);
    };
    detect();
  };

  const drawOverlay = (det: typeof detection) => {
    if (!canvasRef.current || !videoRef.current || !det) return;
    const canvas = canvasRef.current;
    const v = videoRef.current;
    canvas.width = v.videoWidth;
    canvas.height = v.videoHeight;
    const ctx = canvas.getContext('2d')!;
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const box = det.detection.box;
    const score = det.detection.score;
    ctx.strokeStyle = score > 0.85 ? '#10b981' : score > 0.7 ? '#facc15' : '#ef4444';
    ctx.lineWidth = 3;
    ctx.strokeRect(box.x, box.y, box.width, box.height);

    // corner brackets
    const cl = 20;
    ctx.lineWidth = 5;
    ctx.beginPath();
    [[box.x, box.y], [box.x + box.width, box.y], [box.x, box.y + box.height], [box.x + box.width, box.y + box.height]].forEach(([x, y], i) => {
      const dx = i % 2 === 0 ? 1 : -1;
      const dy = i < 2 ? 1 : -1;
      ctx.moveTo(x, y);
      ctx.lineTo(x + cl * dx, y);
      ctx.moveTo(x, y);
      ctx.lineTo(x, y + cl * dy);
    });
    ctx.stroke();
  };

  const clearOverlay = () => {
    const c = canvasRef.current;
    if (!c) return;
    c.getContext('2d')?.clearRect(0, 0, c.width, c.height);
  };

  const captureNow = async (det?: typeof detection) => {
    const det2 = det ?? detection;
    if (!det2 || !videoRef.current) return;
    setCapturing(true);
    try {
      const tmpCanvas = document.createElement('canvas');
      tmpCanvas.width = videoRef.current.videoWidth;
      tmpCanvas.height = videoRef.current.videoHeight;
      tmpCanvas.getContext('2d')!.drawImage(videoRef.current, 0, 0);
      const blob = await new Promise<Blob>((resolve) => tmpCanvas.toBlob((b) => resolve(b!), 'image/jpeg', 0.9));
      onCapture(Array.from(det2.descriptor), blob, det2.detection.score);
    } finally {
      setCapturing(false);
    }
  };

  return (
    <div className="space-y-3">
      <div className="relative rounded-2xl overflow-hidden glass-strong aspect-[4/3] bg-black/50">
        <video ref={videoRef} className="w-full h-full object-cover" muted playsInline />
        <canvas ref={canvasRef} className="absolute inset-0 w-full h-full pointer-events-none" />

        {status === 'loading' && (
          <div className="absolute inset-0 flex flex-col items-center justify-center bg-surface-0/70 backdrop-blur-sm">
            <Loader2 className="h-8 w-8 text-brand-400 animate-spin mb-2" />
            <p className="text-sm text-brand-200">Memuat model & kamera...</p>
          </div>
        )}

        {status === 'error' && (
          <div className="absolute inset-0 flex flex-col items-center justify-center text-center p-6 bg-surface-0/80">
            <AlertCircle className="h-10 w-10 text-red-400 mb-3" />
            <p className="text-sm text-red-200 font-medium mb-1">Tidak bisa akses kamera</p>
            <p className="text-xs text-red-300/70">{errorMsg}</p>
          </div>
        )}

        {status === 'ready' && (
          <div className="absolute top-3 left-3">
            <span className={`badge ${detection ? 'badge-success' : 'badge-warning'}`}>
              {detection ? <CheckCircle2 className="h-3 w-3" /> : <ScanFace className="h-3 w-3" />}
              {detection ? `Wajah Terdeteksi (${Math.round(detection.detection.score * 100)}%)` : 'Memindai wajah...'}
            </span>
          </div>
        )}
      </div>

      {!autoCapture && (
        <button
          type="button"
          disabled={!detection || capturing || status !== 'ready'}
          onClick={() => captureNow()}
          className="btn-primary w-full flex items-center justify-center gap-2"
        >
          {capturing ? <Loader2 className="h-4 w-4 animate-spin" /> : <Camera className="h-4 w-4" />}
          {buttonLabel}
        </button>
      )}
    </div>
  );
}
