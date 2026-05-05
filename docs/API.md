# API Reference — Presensi TSX v2

**Base URL**: `http://localhost:8000/api`
**Auth Method**: Laravel Sanctum (Bearer Token)

---

## Daftar Endpoint Lengkap

### 🌐 Public Endpoints

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/auth/captcha` | Generate captcha challenge baru |
| POST | `/api/auth/login` | Login dengan email + password + captcha |
| POST | `/api/telegram/webhook/{secret}` | Webhook Telegram bot (production) |

### 🔐 Auth Endpoints (require token)

| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/auth/logout` | Logout & revoke token |
| GET | `/api/auth/me` | Get current user info |

### 🔄 Shared (admin & user)

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/cash-categories` | List kategori transaksi |

### 👑 Admin Endpoints (`role:admin`)

#### Dashboard
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/dashboard` | Stats global + chart 7 hari + upcoming events |

#### User Management
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/users` | List users (paginated, filter: q, role, is_active) |
| POST | `/api/admin/users` | Create user baru |
| GET | `/api/admin/users/{user}` | Detail user |
| PUT | `/api/admin/users/{user}` | Update user |
| DELETE | `/api/admin/users/{user}` | Soft delete user |

#### Profile Update Requests
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/profile-requests` | List request perubahan profil pending |
| POST | `/api/admin/profile-requests/{id}/approve` | Setujui perubahan |
| POST | `/api/admin/profile-requests/{id}/reject` | Tolak perubahan |

#### Attendance
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/attendance` | List presensi (filter: date, month, year, status, user_id) |
| GET | `/api/admin/attendance/export` | Export CSV |
| GET | `/api/admin/attendance/export-pdf` | Export PDF (premium-styled) |

#### Cash Flow
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/cash-flow` | List + summary (income/expense/balance) |
| POST | `/api/admin/cash-flow` | Create transaksi |
| PUT | `/api/admin/cash-flow/{id}` | Update / approve |
| DELETE | `/api/admin/cash-flow/{id}` | Soft delete |
| GET | `/api/admin/cash-flow/export` | Export CSV |
| GET | `/api/admin/cash-flow/export-pdf` | Export PDF |

#### Cash Categories
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/cash-categories` | List |
| POST | `/api/admin/cash-categories` | Create |
| PUT | `/api/admin/cash-categories/{id}` | Update |
| DELETE | `/api/admin/cash-categories/{id}` | Delete |

#### Events
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/events` | List events |
| POST | `/api/admin/events` | Create (dengan nested coordinators/speakers/participants) |
| GET | `/api/admin/events/{event}` | Detail |
| PUT | `/api/admin/events/{event}` | Update |
| DELETE | `/api/admin/events/{event}` | Delete |
| GET | `/api/admin/events/stats` | Statistik (total, today, upcoming, past) |
| POST | `/api/admin/events/{event}/coordinators` | Tambah PJ |
| DELETE | `/api/admin/events/{event}/coordinators/{id}` | Hapus PJ |
| POST | `/api/admin/events/{event}/speakers` | Tambah pembicara |
| DELETE | `/api/admin/events/{event}/speakers/{id}` | Hapus pembicara |
| POST | `/api/admin/events/{event}/participants` | Tambah peserta |
| PUT | `/api/admin/events/{event}/participants/{id}` | Update status peserta |
| DELETE | `/api/admin/events/{event}/participants/{id}` | Hapus peserta |

#### Activity Logs
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/logs/users` | Log perubahan user |
| GET | `/api/admin/logs/attendance` | Log presensi |
| GET | `/api/admin/logs/cash-flow` | Log transaksi kas |

#### Settings
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/settings` | List semua settings (grouped) |
| PUT | `/api/admin/settings` | Bulk update |

### 👤 User Endpoints (`role:user,admin`)

#### Dashboard
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/user/dashboard` | Stats personal (today status, monthly, upcoming events) |

#### Profile
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/user/profile` | Profile + pending requests |
| POST | `/api/user/profile/request-update` | Ajukan perubahan field |
| POST | `/api/user/profile/update` | Update langsung (avatar, bio) |

#### Face Recognition
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/user/face/status` | Cek status pendaftaran wajah |
| POST | `/api/user/face/register` | Daftar/update wajah |
| DELETE | `/api/user/face/register` | Hapus pendaftaran |
| POST | `/api/user/face/test` | Test descriptor matching (dev tool) |

#### Attendance
| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/user/check-in` | Check-in dengan face verification |
| POST | `/api/user/check-out` | Check-out |
| GET | `/api/user/riwayat-absen` | Riwayat per bulan |

#### Cash Flow (own)
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/user/cash-flow` | List transaksi sendiri |
| POST | `/api/user/cash-flow` | Ajukan transaksi (status: pending) |

#### Calendar (Khusus Peserta)
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/user/calendar` | Event publik + yg user ikuti |
| GET | `/api/user/calendar/my-participations` | List partisipasi user |
| POST | `/api/user/calendar/{id}/confirm` | Konfirmasi kehadiran |

#### Telegram Self-Connect
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/user/telegram/status` | Cek status koneksi Telegram |
| POST | `/api/user/telegram/link` | Generate deep link koneksi |
| DELETE | `/api/user/telegram/link` | Putuskan koneksi |
| POST | `/api/user/telegram/toggle` | Toggle ON/OFF notifikasi |

---

## Authentication Flow

```
1. GET  /api/auth/captcha               → { id, image (base64 SVG), expires_in }
2. POST /api/auth/login                  ↓
   Body: { email, password, captcha_id, captcha_answer }
                                         ↓
   Response: { user, token }             ↓
3. Setiap request berikutnya:           ↓
   Header: Authorization: Bearer <token>
```

---

## Detail Endpoint Penting

### POST `/api/auth/login`

**Request:**
```json
{
  "email": "admin@presensi.test",
  "password": "admin123",
  "captcha_id": "uuid-string",
  "captcha_answer": "X3K9P"
}
```

**Response 200:**
```json
{
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@presensi.test",
    "role": "admin",
    "nik": "ADM-0001",
    "position": "Administrator",
    "phone": "081234567890",
    "whatsapp_number": "6281234567890",
    "telegram_chat_id": "8549243665",
    "is_active": true,
    "face_registered": false
  },
  "token": "1|abcdef123..."
}
```

**Response 422 (captcha gagal):**
```json
{
  "message": "Captcha tidak valid atau kedaluwarsa.",
  "captcha_failed": true
}
```

**Response 401 (kredensial salah):**
```json
{ "message": "Email atau password salah." }
```

---

### POST `/api/user/check-in`

**Request (multipart/form-data):**
```
photo: <File> (optional)
face_descriptor[0]: 0.123
face_descriptor[1]: -0.456
... (128 entries)
location: "Office - Building A, Floor 3"
latitude: -6.1912
longitude: 106.6673
```

**Response 201:**
```json
{
  "message": "Check-in berhasil",
  "attendance": {
    "id": 12,
    "user_id": 2,
    "type": "in",
    "date": "2026-05-03",
    "time": "08:15:23",
    "face_verified": true,
    "face_score": 0.94,
    "status": "late",
    "location": "..."
  }
}
```

**Response 422 (face fail):**
```json
{
  "message": "Verifikasi wajah gagal. Pastikan wajah jelas & sesuai pendaftaran.",
  "face_verified": false,
  "score": 0.55,
  "distance": 0.45,
  "threshold": 0.40
}
```

---

### POST `/api/user/face/register`

**Request (multipart/form-data):**
```
photo: <File> (jpg/png, max 4MB)
descriptor[0..127]: <float>  (128 entries)
quality_score: 0.94
```

**Response 201:**
```json
{
  "message": "Wajah berhasil didaftarkan",
  "registered": true
}
```

---

### POST `/api/admin/events`

**Request:**
```json
{
  "title": "Workshop Laravel 12",
  "description": "Hands-on workshop",
  "location": "Aula Lt. 3",
  "event_at": "2026-06-15 09:00:00",
  "event_end_at": "2026-06-15 16:00:00",
  "notify_before": 60,
  "category": "workshop",
  "visibility": "public",
  "color": "#fbbf24",
  "coordinators": [
    {
      "name": "Budi Santoso",
      "user_id": 2,
      "role": "Penanggung Jawab",
      "phone": "081234567890",
      "email": "budi@example.com"
    }
  ],
  "speakers": [
    {
      "name": "Dr. Tech",
      "title": "Senior Engineer",
      "organization": "Acme Corp",
      "topic": "Modern Laravel Architecture"
    }
  ],
  "participants": [
    {
      "name": "Raffi Ciputra",
      "email": "raffi@example.com",
      "phone": "08987654321",
      "institution": "Universitas X"
    }
  ]
}
```

**Response 201:** event lengkap dengan semua nested.

---

### POST `/api/user/telegram/link`

**Response:**
```json
{
  "token": "tk_a3f8b9c2d1e4f7g0",
  "deep_link": "https://t.me/Jokoplingbot?start=tk_a3f8b9c2d1e4f7g0",
  "bot_username": "Jokoplingbot",
  "expires_in": 600,
  "instruction": "Klik link → buka Telegram → klik tombol START..."
}
```

---

### GET `/api/user/dashboard`

**Response:**
```json
{
  "today": {
    "check_in": { "id": 12, "time": "08:15:23", "face_verified": true },
    "check_out": null
  },
  "month_stats": {
    "present": 15,
    "late": 3,
    "early_leave": 1
  },
  "face_registered": true,
  "upcoming_events": [
    {
      "id": 5,
      "title": "Rapat Bulanan",
      "event_at": "2026-05-10T10:00:00.000000Z",
      "location": "Ruang Meeting"
    }
  ]
}
```

---

### GET `/api/admin/dashboard`

**Response:**
```json
{
  "stats": {
    "total_users": 25,
    "active_users": 23,
    "today_present": 18,
    "today_absent": 5,
    "monthly_income": 5000000,
    "monthly_expense": 1500000,
    "monthly_balance": 3500000,
    "upcoming_events_count": 3
  },
  "attendance_chart": [
    { "date": "2026-04-27", "label": "Sun", "present": 0, "late": 0 },
    { "date": "2026-04-28", "label": "Mon", "present": 22, "late": 3 },
    ...
  ],
  "upcoming_events": [...]
}
```

---

## Error Response Format

Semua error pakai format konsisten:

**400/404/500:**
```json
{ "message": "Error description" }
```

**422 (validation):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password must be at least 6 characters."]
  }
}
```

**401:**
```json
{
  "message": "Unauthenticated. Token tidak valid atau belum login.",
  "authenticated": false
}
```

**403:**
```json
{
  "message": "Forbidden - role tidak diizinkan",
  "required_roles": ["admin"],
  "your_role": "user"
}
```

---

## Rate Limiting

Default Laravel: **60 req/minute per IP** untuk API routes. Bisa custom via:

```php
// routes/api.php
Route::middleware('throttle:120,1')->group(function () {...});
```

---

## Testing dengan cURL

### Login
```bash
# 1. Get captcha
curl http://localhost:8000/api/auth/captcha
# → { "id": "...", "image": "data:image/svg...", "expires_in": 300 }

# 2. Login (manual ekstrak captcha_answer dari image)
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@presensi.test",
    "password": "admin123",
    "captcha_id": "uuid-from-step-1",
    "captcha_answer": "ABCDE"
  }'
```

### Authenticated Request
```bash
TOKEN="1|abcdef123..."

curl http://localhost:8000/api/user/dashboard \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"
```

---

## Postman Collection

Import URL ini ke Postman buat collection lengkap:
```
{{baseUrl}}/api/auth/login
{{baseUrl}}/api/admin/users
... (semua endpoint di atas)
```

Set environment vars:
- `baseUrl` = `http://localhost:8000`
- `token` = auto-set dari response login (script test)

---

## Versioning

Saat ini **v2** (tidak ada prefix versi di URL). Kalau nanti ada v3, suggest pake `/api/v3/...`.
