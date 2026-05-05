import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatRupiah(n: number | string) {
  const v = typeof n === 'string' ? parseFloat(n) : n;
  return 'Rp ' + v.toLocaleString('id-ID', { maximumFractionDigits: 0 });
}

export function formatDate(d: string | Date, opts?: Intl.DateTimeFormatOptions) {
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('id-ID', opts ?? { day: '2-digit', month: 'short', year: 'numeric' });
}

export function formatDateTime(d: string | Date) {
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

export function getInitials(name: string) {
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

/**
 * Download file dari endpoint API yang butuh auth (kirim token via header).
 * Pake ini buat tombol Export CSV / PDF / dll.
 */
export async function downloadAuthed(url: string, filename?: string) {
  const token = localStorage.getItem('auth_token');
  const baseURL = (import.meta as any).env?.VITE_API_BASE_URL || '/api';
  const fullUrl = url.startsWith('http') ? url : `${baseURL}${url.startsWith('/') ? url : `/${url}`}`;

  const res = await fetch(fullUrl, {
    method: 'GET',
    headers: {
      Accept: '*/*',
      Authorization: token ? `Bearer ${token}` : '',
    },
  });
  if (!res.ok) throw new Error(`Download gagal (${res.status})`);

  const blob = await res.blob();
  const blobUrl = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = blobUrl;
  a.download = filename || url.split('/').pop() || 'download.csv';
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(blobUrl);
}
