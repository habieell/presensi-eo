# Daftar Fitur Lengkap

## Fitur Existing (dipertahankan)

- ✅ Login & Sanctum token auth
- ✅ Manajemen user (admin)
- ✅ Check-in / check-out attendance
- ✅ Riwayat absen per bulan
- ✅ Profile update via approval
- ✅ Cash flow CRUD (admin)
- ✅ Calendar events
- ✅ Settings (jam kerja, dll)
- ✅ Export CSV (attendance, cashflow)
- ✅ Role-based access (admin/user)

## Fitur Baru v2

### 1. WhatsApp Notification
- Service: `app/Services/WhatsApp/FonnteService.php`
- Dispatcher: `app/Services/Notification/NotificationDispatcher.php`
- Trigger:
  - User profile changed → admin
  - Check-in / check-out → user
  - Cash flow → admin
  - Event reminder → peserta (scheduled)
- Outbox tracking di tabel `notification_outbox`

### 2. Database Indexing & Logging
- 16 migration files dengan indexing optimal
- Tabel log baru: `user_logs`, `attendance_logs`, `cash_flow_logs`
- Auto-log via Eloquent observers
- Indexes pada: `user_id+date`, `type+status`, `logged_at`, dll

### 3. Face Recognition
- Library: face-api.js (browser-side)
- Component: `resources/js/components/face/FaceCapture.tsx`
- Service backend: `app/Services/FaceRecognition/FaceMatchService.php`
- Flow:
  1. Register → simpan 128-dim descriptor di `face_registrations`
  2. Check-in → capture descriptor → cocokkan via Euclidean distance
  3. Threshold configurable di `config/face.php`

### 4. Calendar dengan WhatsApp Reminder
- Scheduled command: `php artisan events:send-reminders` (every 5 min)
- Otomatis kirim ke peserta yg notify_before menit lagi
- Update flag `reminder_sent` agar tidak duplikat

### 5. Captcha Login
- Service: `app/Services/Captcha/SimpleCaptchaService.php`
- SVG-based (no GD/imagick needed)
- TTL 5 menit
- Validasi via middleware `verify.captcha`

### 6. Event Module One-to-Many
- 3 tabel relasi:
  - `event_coordinators` (Penanggung Jawab)
  - `event_speakers` (Pembicara)
  - `event_participants` (Peserta)
- Form event dengan dynamic add/remove rows
- Endpoint nested untuk manage tiap entity

### 7. Kalender Khusus Peserta
- Endpoint `/api/user/calendar`
- Filter: visibility=public ATAU user terdaftar sbg participant
- Tampilkan info lengkap (PJ, pembicara, status partisipasi)

### 8. Bottom Navbar Kontras Tinggi
- File: `resources/js/components/layout/BottomNavbar.tsx`
- CSS: `.bottom-nav`, `.bottom-nav-item.active` di `globals.css`
- Active state: gradient background + bar indicator atas + glow shadow
- Berbeda untuk admin & user

### 9. Dropdown Kategori Transaksi
- 11 kategori default (5 income, 6 expense)
- Stored di tabel `cash_categories`
- CRUD admin via `/api/admin/cash-categories`
- Dropdown di form cash flow auto-filter berdasarkan tipe

### 10. Activity Log Viewer
- Page: `resources/js/pages/admin/Logs.tsx`
- 3 tab: User, Attendance, Cash Flow
- Tampilkan: action, before/after, IP, timestamp

### 11. UI Premium
- Glassmorphism + Gradient
- Inter font
- Sonner toast dengan dark glass theme
- Recharts dengan gradient fill
- Animations: float, pulse-glow, shimmer
- Color: brand purple (#7c3aed) + cyan (#06b6d4)
