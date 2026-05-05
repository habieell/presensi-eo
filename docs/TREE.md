# Folder Tree (Clean Architecture)

```
Presensi-tsxlaravel-v2/
│
├── app/                                       ← Backend Laravel
│   ├── Console/Commands/                      ← Artisan commands
│   │   ├── DownloadFaceModels.php             ← Download model face-api.js
│   │   ├── PurgeExpiredCaptchas.php           ← Cleanup captcha (hourly)
│   │   ├── RetryFailedNotifications.php       ← Retry WA gagal (15 min)
│   │   └── SendEventReminders.php             ← Reminder event (every 5 min)
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php                 ← Base controller
│   │   │   └── Api/
│   │   │       ├── Auth/
│   │   │       │   ├── AuthController.php     ← Login + me + logout (+ captcha)
│   │   │       │   └── CaptchaController.php  ← Generate captcha SVG
│   │   │       ├── Admin/
│   │   │       │   ├── DashboardController.php       ← Stats + chart 7 hari
│   │   │       │   ├── UserManagementController.php  ← CRUD user
│   │   │       │   ├── AttendanceController.php      ← List + export CSV
│   │   │       │   ├── CashFlowController.php        ← CRUD + approval
│   │   │       │   ├── CashCategoryController.php    ← Master kategori
│   │   │       │   ├── EventController.php           ← CRUD + nested
│   │   │       │   ├── ProfileRequestController.php  ← Approve/reject perubahan
│   │   │       │   ├── ActivityLogController.php     ← Viewer 3 log
│   │   │       │   └── SettingController.php         ← Pengaturan global
│   │   │       └── User/
│   │   │           ├── DashboardController.php       ← Stats user
│   │   │           ├── CheckInController.php         ← Check-in + face validate
│   │   │           ├── CheckOutController.php        ← Check-out + face validate
│   │   │           ├── ProfileController.php         ← Show + request update
│   │   │           ├── CashFlowController.php        ← User-side cash flow
│   │   │           ├── EventCalendarController.php   ← Kalender peserta
│   │   │           ├── FaceRegistrationController.php← Daftar/update wajah
│   │   │           └── RiwayatAbsenController.php    ← Riwayat per bulan
│   │   ├── Middleware/
│   │   │   ├── Cors.php
│   │   │   ├── RoleMiddleware.php             ← role:admin / role:user
│   │   │   └── VerifyCaptcha.php              ← Auto-validate captcha
│   │   └── Resources/
│   │       └── UserResource.php
│   │
│   ├── Models/                                ← Eloquent Models
│   │   ├── User.php                           ← + WA helper, soft delete
│   │   ├── Attendance.php                     ← + face_verified, status
│   │   ├── AttendanceLog.php                  ← Audit log
│   │   ├── CashCategory.php                   ← Master kategori
│   │   ├── CashFlow.php                       ← + approval, soft delete
│   │   ├── CashFlowLog.php                    ← Audit log
│   │   ├── UserLog.php                        ← Audit log
│   │   ├── ProfileUpdateRequest.php
│   │   ├── FaceRegistration.php               ← 128-dim descriptor
│   │   ├── CalendarEvent.php                  ← + visibility, category
│   │   ├── EventCoordinator.php               ← One-to-many (PJ)
│   │   ├── EventSpeaker.php                   ← One-to-many (Pembicara)
│   │   ├── EventParticipant.php               ← One-to-many (Peserta)
│   │   ├── NotificationOutbox.php             ← Track notif WA
│   │   ├── CaptchaChallenge.php               ← Captcha challenges
│   │   └── Setting.php                        ← Cached settings
│   │
│   ├── Observers/                             ← Auto log + notif on change
│   │   ├── UserObserver.php
│   │   ├── AttendanceObserver.php
│   │   └── CashFlowObserver.php
│   │
│   ├── Services/                              ← Business logic
│   │   ├── WhatsApp/
│   │   │   └── FonnteService.php              ← WA gateway client
│   │   ├── Notification/
│   │   │   └── NotificationDispatcher.php     ← Template + dispatch
│   │   ├── FaceRecognition/
│   │   │   └── FaceMatchService.php           ← Euclidean matching
│   │   ├── Captcha/
│   │   │   └── SimpleCaptchaService.php       ← SVG-based captcha
│   │   └── ActivityLog/
│   │       └── ActivityLogger.php             ← Insert ke 3 log table
│   │
│   └── Providers/
│       ├── AppServiceProvider.php
│       └── EventServiceProvider.php           ← Register observers
│
├── bootstrap/
│   ├── app.php                                ← Middleware + alias
│   └── providers.php
│
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── captcha.php       ← Captcha settings
│   ├── database.php
│   ├── face.php          ← Face recognition threshold
│   ├── filesystems.php
│   ├── fonnte.php        ← WhatsApp gateway
│   └── sanctum.php
│
├── database/
│   ├── migrations/                            ← 19 migrations
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   ├── 0001_01_01_000002_create_jobs_table.php
│   │   ├── 2026_05_01_000000_create_personal_access_tokens_table.php
│   │   ├── 2026_05_01_000001_create_settings_table.php
│   │   ├── 2026_05_01_000002_create_attendance_table.php       ← + INDEX
│   │   ├── 2026_05_01_000003_create_face_registrations_table.php
│   │   ├── 2026_05_01_000004_create_cash_categories_table.php  ← + 11 default kategori
│   │   ├── 2026_05_01_000005_create_cash_flow_table.php        ← + INDEX, soft delete
│   │   ├── 2026_05_01_000006_create_profile_update_requests_table.php
│   │   ├── 2026_05_01_000007_create_calendar_events_table.php  ← + visibility
│   │   ├── 2026_05_01_000008_create_event_coordinators_table.php
│   │   ├── 2026_05_01_000009_create_event_speakers_table.php
│   │   ├── 2026_05_01_000010_create_event_participants_table.php
│   │   ├── 2026_05_01_000011_create_attendance_logs_table.php  ← LOG (revisi)
│   │   ├── 2026_05_01_000012_create_cash_flow_logs_table.php   ← LOG (revisi)
│   │   ├── 2026_05_01_000013_create_user_logs_table.php        ← LOG (revisi)
│   │   ├── 2026_05_01_000014_create_notification_outbox_table.php
│   │   └── 2026_05_01_000015_create_captcha_challenges_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       └── UserSeeder.php
│
├── public/
│   ├── .htaccess
│   ├── index.php
│   ├── robots.txt
│   └── face-models/                           ← Model face-api.js (download via artisan)
│
├── resources/                                 ← Frontend
│   ├── js/
│   │   ├── app.tsx                            ← Entry React
│   │   ├── Router.tsx                         ← React Router config
│   │   │
│   │   ├── components/
│   │   │   ├── face/
│   │   │   │   └── FaceCapture.tsx            ← Webcam + face-api.js
│   │   │   └── layout/
│   │   │       ├── AppLayout.tsx
│   │   │       ├── Topbar.tsx
│   │   │       └── BottomNavbar.tsx           ← KONTRAS TINGGI
│   │   │
│   │   ├── contexts/
│   │   │   └── AuthContext.tsx                ← Global auth state
│   │   │
│   │   ├── lib/
│   │   │   ├── axios.ts                       ← API client + interceptors
│   │   │   └── utils.ts                       ← cn, formatRupiah, formatDate
│   │   │
│   │   ├── pages/
│   │   │   ├── auth/
│   │   │   │   └── Login.tsx                  ← Login + CAPTCHA
│   │   │   ├── user/
│   │   │   │   ├── Dashboard.tsx
│   │   │   │   ├── CheckIn.tsx                ← + Face capture
│   │   │   │   ├── FaceRegister.tsx           ← Daftar wajah
│   │   │   │   ├── Profile.tsx                ← Request update
│   │   │   │   ├── Calendar.tsx               ← Kalender peserta
│   │   │   │   ├── CashFlow.tsx               ← + DROPDOWN kategori
│   │   │   │   └── RiwayatAbsen.tsx
│   │   │   └── admin/
│   │   │       ├── Dashboard.tsx              ← + Recharts
│   │   │       ├── Users.tsx
│   │   │       ├── Attendance.tsx             ← + Export CSV
│   │   │       ├── CashFlow.tsx               ← + DROPDOWN kategori + approval
│   │   │       ├── Events.tsx                 ← + One-to-many nested form
│   │   │       ├── Logs.tsx                   ← 3 tab activity log
│   │   │       └── Settings.tsx
│   │   │
│   │   └── styles/
│   │       └── globals.css                    ← Tailwind v4 + GLASSMORPHISM
│   │
│   └── views/
│       └── app.blade.php                      ← SPA shell
│
├── routes/
│   ├── api.php                                ← REST API
│   ├── web.php                                ← SPA fallback
│   └── console.php                            ← Scheduled tasks
│
├── storage/                                   ← Auto-managed
│   ├── app/public/{attendance,avatars,face-registration}
│   ├── framework/{cache,sessions,testing,views}
│   └── logs/
│
├── docs/                                      ← Dokumentasi
│   ├── FEATURES.md                            ← Detail semua fitur
│   ├── SETUP.md                               ← Panduan instalasi
│   └── TREE.md                                ← File ini
│
├── tests/                                     ← (kosong, siap diisi)
│
├── .env.example                               ← Template env (Fonnte, face, captcha)
├── .gitignore
├── README.md                                  ← Main docs
├── artisan
├── composer.json                              ← + Fonnte deps
├── package.json                               ← + face-api.js, recharts, sonner, framer-motion
├── postcss.config.js
├── tsconfig.json
└── vite.config.ts
```

## Quick Stats

- **Total file**: ~115 (production code)
- **Backend (PHP)**: 90 files
- **Frontend (TSX/TS)**: 25 files
- **Migrations**: 19 (16 baru + 3 default)
- **Models**: 16 (10 baru)
- **Controllers**: 20 (Admin: 9, User: 8, Auth: 2, Base: 1)
- **Services**: 5 (WhatsApp, Notification, Face, Captcha, ActivityLog)
- **Observers**: 3 (User, Attendance, CashFlow)
- **Middleware**: 3 (Cors, Role, VerifyCaptcha)
