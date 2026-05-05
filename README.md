# Presensi TSX v2

Sistem **Presensi & Manajemen Modern** dengan face recognition, notifikasi WhatsApp, dan event calendar lengkap. Built dengan Laravel 12 + React 19 + TypeScript + Tailwind v4.

## Highlight Fitur Baru (v2)

| # | Fitur | Status |
|---|---|---|
| 1 | Notifikasi **WhatsApp** untuk admin (saat user ubah profil) — via Fonnte | ✓ |
| 2 | **Indexing DB** + tabel log untuk attendance, kas, & user data | ✓ |
| 3 | **Face Recognition** (face-api.js) untuk register / check-in / check-out | ✓ |
| 4 | **Calendar dengan WhatsApp reminder** otomatis (scheduled) | ✓ |
| 5 | **Captcha** untuk login (built-in, lightweight SVG) | ✓ |
| 6 | **Event** dengan **Penanggung Jawab, Pembicara, Peserta** (one-to-many) | ✓ |
| 7 | **Kalender khusus peserta** — hanya tampilkan event publik & yang user ikuti | ✓ |
| 8 | **Bottom Navbar kontras tinggi** dengan glass effect & active indicator | ✓ |
| 9 | **Dropdown kategori transaksi** dengan kategorisasi income/expense | ✓ |
| 10 | **Activity Log** untuk attendance, alur kas, data user (audit trail) | ✓ |
| 11 | UI Premium **Glassmorphism + Gradient** ala Linear/Vercel | ✓ |

---

## Tech Stack

**Backend**
- Laravel 12 (PHP 8.2+)
- MySQL 8.4
- Sanctum (token auth)
- Guzzle (HTTP)
- Spatie Activity Log

**Frontend**
- React 19 + TypeScript
- Vite 6 + Tailwind CSS v4
- face-api.js
- Recharts (chart)
- Sonner (toast)
- React Router 7

---

## Struktur Folder

```
Presensi-tsxlaravel-v2/
├── app/
│   ├── Console/Commands/         # SendEventReminders, PurgeCaptchas, DownloadFaceModels, RetryFailedNotifications
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── Auth/             # AuthController, CaptchaController
│   │   │   ├── Admin/            # Dashboard, UserManagement, Attendance, CashFlow, CashCategory, Event, ActivityLog, ProfileRequest, Setting
│   │   │   └── User/             # Dashboard, CheckIn, CheckOut, Profile, CashFlow, EventCalendar, FaceRegistration, RiwayatAbsen
│   │   ├── Middleware/           # Cors, RoleMiddleware, VerifyCaptcha
│   │   └── Resources/            # UserResource
│   ├── Models/                   # 14 model dengan relationship lengkap
│   ├── Observers/                # UserObserver, AttendanceObserver, CashFlowObserver — auto log + notif
│   ├── Services/
│   │   ├── WhatsApp/FonnteService.php
│   │   ├── Notification/NotificationDispatcher.php
│   │   ├── FaceRecognition/FaceMatchService.php
│   │   ├── Captcha/SimpleCaptchaService.php
│   │   └── ActivityLog/ActivityLogger.php
│   └── Providers/
├── config/
│   ├── fonnte.php       # WA gateway config
│   ├── face.php         # Face recognition threshold
│   ├── captcha.php      # Captcha settings
│   └── (app, auth, database, sanctum, ...)
├── database/
│   ├── migrations/      # 16 migrations dengan indexing optimal
│   └── seeders/         # UserSeeder (default admin & user)
├── resources/
│   ├── js/
│   │   ├── components/
│   │   │   ├── face/FaceCapture.tsx       # Webcam + face-api.js
│   │   │   └── layout/                    # AppLayout, Topbar, BottomNavbar (kontras)
│   │   ├── contexts/AuthContext.tsx
│   │   ├── lib/                           # axios, utils
│   │   ├── pages/
│   │   │   ├── auth/Login.tsx             # + captcha
│   │   │   ├── user/                      # Dashboard, CheckIn, FaceRegister, Profile, Calendar, CashFlow, RiwayatAbsen
│   │   │   └── admin/                     # Dashboard, Users, Attendance, CashFlow, Events, Logs, Settings
│   │   ├── styles/globals.css             # Tailwind v4 + custom premium classes
│   │   ├── app.tsx
│   │   └── Router.tsx
│   └── views/app.blade.php                # SPA shell
├── routes/
│   ├── api.php          # REST endpoints
│   ├── web.php          # SPA fallback
│   └── console.php      # Scheduled tasks
├── public/face-models/  # face-api.js model weights (download via artisan)
└── ...
```

---

## Database Schema (Revisi)

### Tabel Baru
- `cash_categories` — kategori transaksi (Iuran, Donasi, Operasional, dll)
- `event_coordinators` — penanggung jawab event (one-to-many)
- `event_speakers` — pembicara event (one-to-many)
- `event_participants` — peserta event (one-to-many)
- `attendance_logs` — audit trail presensi
- `cash_flow_logs` — audit trail alur kas
- `user_logs` — audit trail data user
- `notification_outbox` — track semua notif WA
- `captcha_challenges` — captcha challenges

### Indexing yang Ditambahkan
```sql
-- attendance
INDEX (user_id, date), (user_id, date, type), (date, type), (status), (created_at)

-- cash_flow
INDEX (date, type), (type, status), (created_by, date), (category_id, date), (status)

-- users
INDEX (role), (is_active), (role, is_active), (phone), (whatsapp_number)

-- calendar_events
INDEX (event_at), (event_at, is_active), (category), (reminder_sent), (visibility, event_at)

-- *_logs (semua tabel log)
INDEX (logged_at), (action, logged_at), (user_id, logged_at)
```

---

## Instalasi

### 1) Prasyarat
- PHP 8.2+
- Composer 2+
- Node 20+ (npm/pnpm)
- MySQL 8+ (atau PostgreSQL)
- HTTPS / localhost (face-api.js butuh `getUserMedia` yang hanya jalan di secure context)

### 2) Setup
```bash
cd Presensi-tsxlaravel-v2

# Backend
composer install
cp .env.example .env
php artisan key:generate

# Edit .env, set DB & Fonnte credentials:
#   DB_DATABASE=presensi_tsx
#   DB_USERNAME=root
#   DB_PASSWORD=
#   FONNTE_TOKEN=...
#   ADMIN_WA_NUMBERS=628xxxxxxxxxx,628xxxxxxxxxx

php artisan storage:link
php artisan migrate --seed

# Download face-api.js models ke public/face-models/
php artisan face:download-models

# Frontend
npm install
npm run build       # production build
# atau
npm run dev         # development with HMR
```

### 3) Jalankan
```bash
# All-in-one (server + queue + scheduler + vite):
composer dev

# atau manual:
php artisan serve            # http://localhost:8000
php artisan queue:listen
php artisan schedule:work
npm run dev
```

### 4) Login Demo
- **Admin**: `admin@presensi.test` / `admin123`
- **User**: `user@presensi.test` / `user123`

---

## Konfigurasi WhatsApp (Fonnte)

1. Daftar di https://fonnte.com
2. Connect device WhatsApp → dapatkan **Device Token**
3. Set di `.env`:
   ```
   FONNTE_TOKEN=your_device_token_here
   FONNTE_DEVICE=your_device_id
   ADMIN_WA_NUMBERS=628123456789,628987654321   # nomor admin penerima notif
   WHATSAPP_NOTIFICATION_ENABLED=true
   ```
4. Notifikasi otomatis terkirim saat:
   - User mengubah / mengajukan perubahan data profil → ke admin
   - Check-in / check-out → ke user
   - Cash flow created → ke admin
   - Event reminder → ke peserta (via scheduler)

---

## Face Recognition

- **Library**: face-api.js (browser-based, tanpa server tambahan)
- **Model**: TinyFaceDetector + Landmark68 + FaceRecognition (128-dim descriptor)
- **Algoritma**: Euclidean distance, threshold 0.55 (configurable di `config/face.php`)

### Flow:
1. User → `/face-register` → kamera capture wajah → ekstrak descriptor → POST ke `/api/user/face/register`
2. User → `/check-in` → kamera capture → bandingkan descriptor dengan yg tersimpan → server validate

### Atur Sensitivitas:
```php
// config/face.php
'match_threshold' => 0.55,  // < 0.55 = match. lebih kecil = lebih ketat
```

---

## Activity Logs

Auto-tracked via Eloquent observers:

| Aksi | Logged ke | Notif WA |
|---|---|---|
| User created/updated/deleted | `user_logs` | Admin |
| Profile request | `user_logs` | Admin |
| Login / failed login | `user_logs` | - |
| Check-in / check-out | `attendance_logs` | User |
| Face registered | `user_logs` | - |
| Cash flow created/updated/deleted | `cash_flow_logs` | Admin |

Lihat semua log di **Admin → Log** (3 tab: User, Absensi, Kas).

---

## Scheduled Tasks

```php
// routes/console.php
Schedule::command('events:send-reminders')->everyFiveMinutes();
Schedule::command('captcha:purge')->hourly();
Schedule::command('notifications:retry')->everyFifteenMinutes();
```

Pastikan cron berjalan:
```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

## API Reference (singkat)

### Auth (public)
| Method | Endpoint | Body |
|---|---|---|
| GET | `/api/auth/captcha` | - |
| POST | `/api/auth/login` | `{email, password, captcha_id, captcha_answer}` |

### User (authenticated)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/user/dashboard` | Dashboard data |
| GET | `/api/user/face/status` | Status registrasi wajah |
| POST | `/api/user/face/register` | Register/update face descriptor |
| POST | `/api/user/check-in` | Check-in (multipart, butuh face_descriptor) |
| POST | `/api/user/check-out` | Check-out |
| GET | `/api/user/calendar` | Event yg user ikuti |
| POST | `/api/user/calendar/{id}/confirm` | Konfirmasi hadir |

### Admin (role:admin)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/admin/dashboard` | Stats global |
| CRUD | `/api/admin/users` | Manajemen user |
| CRUD | `/api/admin/events` | Event |
| POST | `/api/admin/events/{event}/coordinators` | Tambah PJ |
| POST | `/api/admin/events/{event}/speakers` | Tambah pembicara |
| POST | `/api/admin/events/{event}/participants` | Tambah peserta |
| GET | `/api/admin/logs/{users\|attendance\|cash-flow}` | Activity log |

---

## Premium UI Features

- **Glassmorphism**: `backdrop-filter: blur(20px)` + gradient backgrounds
- **Bottom Navbar**: kontras tinggi dengan active indicator (gradient bar di atas)
- **Animations**: float, pulse-glow, shimmer skeleton
- **Toast**: Sonner dengan dark glass theme
- **Charts**: Recharts dengan gradient fill
- **Color Palette**: brand purple/cyan gradient yg konsisten

---

## Troubleshooting

**Face-api.js tidak load model**
- Pastikan file model ada di `public/face-models/`
- Run `php artisan face:download-models`
- Cek di browser DevTools Network tab

**WhatsApp tidak terkirim**
- Cek `FONNTE_TOKEN` di `.env`
- Cek tabel `notification_outbox` untuk lihat error message
- `php artisan notifications:retry`

**Captcha selalu gagal**
- Pastikan TTL belum kadaluarsa (default 5 menit)
- Captcha case-sensitive (lihat `CAPTCHA_SENSITIVE`)

---

## Default Credentials

| Role | Email | Password |
|---|---|---|
| Admin | `admin@presensi.test` | `admin123` |
| User | `user@presensi.test` | `user123` |

**⚠️ GANTI password ini di production!**

---

## License

MIT — bebas dipakai untuk skripsi/freelance/komersial.
