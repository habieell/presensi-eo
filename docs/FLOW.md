# Application Flow Diagrams — Presensi TSX v2

Semua diagram pakai **Mermaid** (langsung kerender di GitHub/VSCode preview).

---

## 1. Login Flow (with Captcha)

```mermaid
sequenceDiagram
    autonumber
    participant U as User Browser
    participant FE as React SPA
    participant API as Laravel API
    participant CDB as captcha_challenges
    participant UDB as users
    participant LOG as user_logs

    U->>FE: Buka /login
    FE->>API: GET /api/auth/captcha
    API->>CDB: INSERT challenge<br/>(uuid, code, expires_at)
    API-->>FE: { id, image (SVG base64), expires_in }
    FE->>U: Render captcha image

    U->>FE: Input email + password + jawab captcha
    FE->>API: POST /api/auth/login<br/>{ email, password, captcha_id, captcha_answer }

    API->>CDB: SELECT WHERE id=?
    alt Captcha invalid/expired
        API-->>FE: 422 { captcha_failed: true }
        FE->>API: GET captcha lagi (refresh)
    end

    API->>CDB: UPDATE used=true
    API->>UDB: SELECT WHERE email=?

    alt Password salah
        API->>LOG: insert action='failed_login'
        API-->>FE: 401 { message }
    else Password OK
        API->>API: createToken('auth-token')
        API->>LOG: insert action='login'
        API-->>FE: 200 { user, token }
        FE->>FE: localStorage.setItem('auth_token', ...)
        FE->>U: redirect to /dashboard or /admin
    end
```

---

## 2. Check-In Flow (with Face Recognition)

```mermaid
sequenceDiagram
    autonumber
    participant U as User
    participant FE as CheckIn.tsx
    participant Cam as Webcam<br/>+ face-api.js
    participant API as Laravel API
    participant FRS as FaceMatchService
    participant DB as Database
    participant TG as Telegram

    U->>FE: Buka /check-in
    FE->>FE: Mount FaceCapture component
    FE->>Cam: getUserMedia({ video: true })
    Cam-->>FE: video stream
    FE->>Cam: Load 3 models:<br/>tinyFaceDetector +<br/>faceLandmark68 +<br/>faceRecognition

    loop Detection (every frame ~16ms)
        FE->>Cam: detectSingleFace()<br/>.withFaceLandmarks()<br/>.withFaceDescriptor()
        Cam-->>FE: { box, score, descriptor[128] }
        FE->>FE: Draw overlay box (green=good)
    end

    U->>FE: Klik "Check-In Sekarang"
    FE->>FE: Capture canvas snapshot (Blob)
    FE->>FE: Get GPS location
    FE->>API: POST /api/user/check-in<br/>(multipart: photo, descriptor[], lat/lng)

    API->>DB: SELECT face_registrations<br/>WHERE user_id=? AND is_active=1
    DB-->>API: { descriptor: [128 floats] }

    API->>FRS: matchUser(descriptor)
    FRS->>FRS: distance = euclidean(input, stored)
    FRS->>FRS: matched = distance < 0.40
    FRS-->>API: { matched, distance, score }

    alt Face NOT matched
        API->>DB: INSERT attendance_logs<br/>action='face_failed'
        API-->>FE: 422 { face_verified: false, score, threshold }
        FE->>U: Toast "Verifikasi wajah gagal"
    else Face matched
        API->>API: Determine status (on_time/late)<br/>by Setting work_start_time
        API->>DB: INSERT attendance<br/>(face_verified=1, score, location...)
        Note over DB: AttendanceObserver triggered
        DB->>DB: INSERT attendance_logs<br/>action='check_in'
        API->>TG: NotificationDispatcher<br/>.attendanceCheckIn($user)
        TG-->>U: 📩 "Check-in berhasil"
        API-->>FE: 201 { attendance }
        FE->>U: Toast success + redirect dashboard
    end
```

---

## 3. Face Registration Flow

```mermaid
flowchart TD
    Start([User buka<br/>/face-register]) --> CheckStatus{Sudah<br/>terdaftar?}
    CheckStatus -->|Ya| ShowExisting[Tampilkan info<br/>+ tombol Hapus + Update]
    CheckStatus -->|Tidak| OpenCamera[Buka webcam]

    ShowExisting --> CamReady
    OpenCamera --> CamReady{Kamera<br/>siap?}
    CamReady -->|Tidak| ShowError[Error: izin ditolak<br/>atau model gagal load]
    CamReady -->|Ya| LoadModels[Load 3 face-api models]

    LoadModels --> DetectLoop[Mulai deteksi<br/>setiap frame]
    DetectLoop --> FaceDetected{Wajah<br/>terdeteksi?}
    FaceDetected -->|Tidak| DetectLoop
    FaceDetected -->|Ya| DrawBox[Gambar overlay<br/>kotak hijau]

    DrawBox --> WaitClick{User klik<br/>'Daftarkan'?}
    WaitClick -->|Tidak| DetectLoop
    WaitClick -->|Ya| Capture[Capture snapshot<br/>+ extract descriptor 128-dim]

    Capture --> POST[POST /api/user/face/register<br/>FormData]
    POST --> Validate{Validasi OK?<br/>length=128, all numeric}
    Validate -->|Tidak| Error[Error: format invalid]
    Validate -->|Ya| SavePhoto[Simpan foto ke<br/>storage/face-registration]

    SavePhoto --> SaveDB[(Insert/Update<br/>face_registrations<br/>updateOrCreate by user_id)]
    SaveDB --> LogAction[ActivityLogger::user<br/>action='face_registered']
    LogAction --> Toast[Toast 'Berhasil!']
    Toast --> Redirect([Redirect /dashboard])

    style Start fill:#fbbf24,color:#000
    style Redirect fill:#10b981,color:#000
    style Error fill:#ef4444,color:#fff
    style ShowError fill:#ef4444,color:#fff
```

---

## 4. Telegram Self-Connect Flow (Opsi B)

```mermaid
sequenceDiagram
    autonumber
    participant U as User
    participant FE as Profile.tsx
    participant API as Laravel API
    participant DB as users table
    participant Bot as Telegram Bot
    participant Poll as PollTelegramUpdates<br/>artisan command
    participant LS as TelegramLinkService

    U->>FE: Buka Profil
    FE->>API: GET /api/user/telegram/status
    API-->>FE: { linked: false }
    FE->>U: Tampilkan card<br/>"Hubungkan Telegram"

    U->>FE: Klik "Hubungkan Telegram"
    FE->>API: POST /api/user/telegram/link
    API->>API: Generate token tk_xxx (16 hex)
    API->>DB: UPDATE user<br/>SET telegram_link_token=tk_xxx
    API->>API: Get bot username via getMe
    API-->>FE: { token, deep_link, bot_username }

    FE->>FE: window.open(deep_link)
    Note right of FE: Tab baru: t.me/Bot?start=tk_xxx
    FE->>FE: Mulai polling status setiap 3s

    U->>Bot: Buka Telegram, klik START

    loop Polling Telegram (parallel)
        Poll->>Bot: GET /getUpdates?offset=N
        Bot-->>Poll: [{ message: { text: '/start tk_xxx', from, chat } }]
        Poll->>LS: processUpdate($update)
        LS->>LS: Regex match /start tk_xxx
        LS->>DB: SELECT user WHERE telegram_link_token=tk_xxx

        alt Token valid
            LS->>DB: UPDATE user SET<br/>telegram_chat_id=chat.id,<br/>telegram_username=from.username,<br/>telegram_link_token=NULL,<br/>telegram_linked_at=NOW()
            LS->>Bot: sendMessage<br/>"✅ Berhasil Terhubung!"
            Bot-->>U: Notif sukses
        else Token expired/invalid
            LS->>Bot: "❌ Tautan tidak valid"
        end
    end

    loop Polling FE (every 3s)
        FE->>API: GET /api/user/telegram/status
        API-->>FE: { linked: true, chat_id, username }
        FE->>FE: Stop polling
        FE->>U: Toast "Telegram terhubung!"
        FE->>FE: Refresh card → tampil status linked
    end
```

---

## 5. Event Reminder (Scheduled)

```mermaid
flowchart TD
    Cron[Cron jalan tiap menit] --> Sched[php artisan schedule:run]
    Sched --> Check{events:send-reminders<br/>schedule trigger?<br/>every 5 min}
    Check -->|Tidak| Skip([Skip])
    Check -->|Ya| Cmd[Run SendEventReminders command]

    Cmd --> Query[(SELECT events<br/>WHERE reminder_sent=0<br/>AND is_active=1<br/>AND event_at > NOW)]
    Query --> Filter{Filter event<br/>yg notify_before menit lagi}
    Filter --> Loop[Loop tiap event]

    Loop --> LoadParts[Load participants + user]
    LoadParts --> ForEach[For each participant]

    ForEach --> HasTG{user punya<br/>telegram_chat_id?}
    HasTG -->|Ya| TGSend[Send Telegram]
    HasTG -->|Tidak| HasWA{punya WA?}
    HasWA -->|Ya| WASend[Send WhatsApp]
    HasWA -->|Tidak| Skip2[Skip participant]

    TGSend --> SaveOutbox[Insert notification_outbox]
    WASend --> SaveOutbox
    SaveOutbox --> MarkSent[UPDATE participant<br/>reminder_sent=1]
    MarkSent --> NextPart{Next participant?}

    NextPart -->|Ya| ForEach
    NextPart -->|Tidak| MarkEvent[UPDATE event<br/>reminder_sent=1]
    MarkEvent --> NextEvent{Next event?}
    NextEvent -->|Ya| Loop
    NextEvent -->|Tidak| Done([Done])

    style Cron fill:#3b82f6,color:#fff
    style Done fill:#10b981,color:#000
```

---

## 6. Profile Update Approval Flow

```mermaid
sequenceDiagram
    participant U as User
    participant FE as Profile.tsx (Modal)
    participant API as Laravel API
    participant DB as Database
    participant TG as Telegram (Admin)
    participant A as Admin

    U->>FE: Buka modal Edit Profile
    U->>FE: Edit beberapa field, klik "Ajukan Perubahan"
    FE->>FE: Detect field yang berubah dari initial
    Note right of FE: changed = [<br/>{field:'phone', new:'08...'},<br/>{field:'address', new:'...'}<br/>]

    loop For each changed field
        FE->>API: POST /api/user/profile/request-update<br/>{ field, new_value }
        API->>DB: INSERT profile_update_requests<br/>(user_id, field, old_value, new_value, status='pending')
        API->>TG: NotificationDispatcher<br/>.profileChanged($user, $field, ...)
        TG-->>A: 📩 Notif "User X minta ubah Y"
    end

    API-->>FE: { request: ... }
    FE->>U: Toast "X permintaan dikirim"
    FE->>FE: Close modal + reload data

    Note over A: Beberapa saat kemudian
    A->>FE: Login admin → Menu Profile Requests
    FE->>API: GET /api/admin/profile-requests
    API-->>FE: List pending requests

    alt Approve
        A->>FE: Klik "Approve"
        FE->>API: POST /api/admin/profile-requests/{id}/approve
        API->>DB: UPDATE users SET {field}=new_value
        Note over DB: UserObserver triggered<br/>auto log + notif
        API->>DB: UPDATE profile_update_requests<br/>SET status='approved'
        API-->>FE: { message: 'Disetujui' }
    else Reject
        A->>FE: Klik "Reject" + alasan
        FE->>API: POST /api/admin/profile-requests/{id}/reject
        API->>DB: UPDATE status='rejected'<br/>+ admin_notes
    end
```

---

## 7. Cash Flow Approval Flow

```mermaid
flowchart TD
    Start([User pengen<br/>tambah kas]) --> Form[Form di /cash-flow]
    Form --> Submit[POST /api/user/cash-flow]
    Submit --> Save[(INSERT cash_flow<br/>status='pending')]
    Save --> Obs[CashFlowObserver fire]
    Obs --> Log[(INSERT cash_flow_logs<br/>action='created')]
    Obs --> Notif[Send notif ke admin]

    Notif --> Wait{Admin<br/>buka dashboard?}
    Wait -->|Ya| AdminView[Admin lihat list pending]
    AdminView --> Decision{Approve atau<br/>Reject?}
    Decision -->|Approve| Update[(UPDATE status='approved'<br/>approved_by=admin)]
    Decision -->|Reject| Reject[(UPDATE status='rejected')]
    Update --> Done([Tampil di summary kas])
    Reject --> NotifUser[Notif user transaksi ditolak]

    style Start fill:#fbbf24,color:#000
    style Done fill:#10b981,color:#000
    style NotifUser fill:#ef4444,color:#fff
```

---

## 8. Architecture: Notif Multi-Channel

```mermaid
flowchart LR
    Trigger[Event trigger:<br/>user updated /<br/>check-in /<br/>cash flow] --> Disp[NotificationDispatcher]

    Disp --> Format[Format pesan:<br/>greeting + detail + emoji]
    Format --> Branch1{Telegram<br/>enabled?}
    Format --> Branch2{WhatsApp<br/>enabled?}

    Branch1 -->|Ya| TGS[TelegramService.send]
    Branch2 -->|Ya| WAS[FonnteService.send]

    TGS --> TGAPI[POST api.telegram.org<br/>/bot{TOKEN}/sendMessage]
    WAS --> WAAPI[POST api.fonnte.com/send<br/>Authorization: token]

    TGAPI --> Outbox1[(Insert notification_outbox<br/>channel='telegram')]
    WAAPI --> Outbox2[(Insert notification_outbox<br/>channel='whatsapp')]

    TGAPI -->|Success| TGOK[User dapet notif Telegram]
    WAAPI -->|Success| WAOK[User dapet notif WA]
    TGAPI -->|Fail| TGFail[status='failed'<br/>retry by scheduler]
    WAAPI -->|Fail| WAFail[status='failed']

    style TGS fill:#0088cc,color:#fff
    style WAS fill:#25d366,color:#fff
    style TGOK fill:#10b981,color:#000
    style WAOK fill:#10b981,color:#000
```

---

## 9. Activity Log Auto-Trigger

```mermaid
flowchart LR
    subgraph Eloquent
      Create[Model::create] --> Created[created event]
      Update[Model::update] --> Updated[updated event]
      Delete[Model::delete] --> Deleted[deleted event]
    end

    subgraph Observers
      Created --> UO[UserObserver]
      Updated --> UO
      Deleted --> UO
      Created --> AO[AttendanceObserver]
      Updated --> AO
      Created --> CO[CashFlowObserver]
      Updated --> CO
      Deleted --> CO
    end

    UO --> ALU[ActivityLogger::user]
    UO --> NDU[NotificationDispatcher::profileChanged]
    AO --> ALA[ActivityLogger::attendance]
    CO --> ALC[ActivityLogger::cashFlow]
    CO --> NDC[NotificationDispatcher::cashFlowChanged]

    ALU --> UL[(user_logs)]
    ALA --> AL[(attendance_logs)]
    ALC --> CL[(cash_flow_logs)]
    NDU --> NotifAdmin[📩 Notif Admin]
    NDC --> NotifAdmin
```

---

## 10. Captcha Generation Flow

```mermaid
sequenceDiagram
    participant Client
    participant API
    participant CSS as SimpleCaptchaService
    participant DB as captcha_challenges

    Client->>API: GET /api/auth/captcha
    API->>CSS: generate(ip)
    CSS->>CSS: randomCode(len=5)<br/>chars=ABCDEFGHJKLMNPQRSTUVWXYZ23456789
    CSS->>DB: INSERT (id=uuid, code, ip,<br/>expires_at=now+5min, used=false)
    CSS->>CSS: renderSvg(code):<br/>1. Gradient bg<br/>2. 6 noise lines<br/>3. 30 dots<br/>4. Text rotated random
    CSS->>CSS: base64 encode SVG
    CSS-->>API: { id, image, expires_in }
    API-->>Client: 200 { id, image: 'data:image/svg+xml;base64,...' }

    Note over Client: User input answer
    Client->>API: POST /api/auth/login<br/>{ ..., captcha_id, captcha_answer }
    API->>CSS: verify(id, answer, ip)
    CSS->>DB: SELECT WHERE id=?
    alt Not found OR used OR expired
        CSS-->>API: false
        API-->>Client: 422 captcha_failed
    else Valid
        CSS->>DB: UPDATE used=true (prevent replay)
        CSS->>CSS: hash_equals($stored, $given)
        CSS-->>API: true/false
    end
```

---

## 11. PDF Export Flow

```mermaid
sequenceDiagram
    participant Admin
    participant FE as React
    participant API as Laravel API
    participant Ctrl as AttendanceController
    participant View as Blade view
    participant Pdf as dompdf

    Admin->>FE: Klik tombol "Export PDF"
    FE->>FE: downloadAuthed() helper<br/>(fetch with Bearer header)
    FE->>API: GET /api/admin/attendance/export-pdf?month=5&year=2026

    API->>Ctrl: exportPdf($request)
    Ctrl->>Ctrl: Query attendance + user
    Ctrl->>View: Pdf::loadView('reports.attendance', [...])
    View->>View: Render HTML<br/>(gradient header, table, summary)
    View->>Pdf: dompdf process HTML
    Pdf-->>Ctrl: $pdf binary
    Ctrl-->>API: $pdf->download('attendance-Mei 2026.pdf')

    API-->>FE: HTTP response (Content-Type: application/pdf)
    FE->>FE: Convert to Blob → URL.createObjectURL
    FE->>FE: Trigger <a download>
    FE->>Admin: File ke-download
```

---

## 12. High-Level System Architecture

```mermaid
flowchart TB
    subgraph Client["Client Layer"]
      Browser[Web Browser<br/>React SPA]
      Mobile[Mobile<br/>future]
      AdminPanel[Admin Console]
    end

    subgraph Server["Laravel Server"]
      direction TB
      Routes[routes/api.php]
      MW[Middleware Stack:<br/>CORS → Sanctum → Role → Captcha]
      Controllers
      Services
      Models[Eloquent ORM]
      Observers
      Schedule[Scheduler<br/>events:send-reminders<br/>captcha:purge<br/>notifications:retry<br/>telegram:poll]
    end

    subgraph DB["MySQL Database"]
      direction TB
      Core[(Core tables:<br/>users, attendance,<br/>cash_flow, events)]
      Logs[(Audit logs:<br/>user_logs,<br/>attendance_logs,<br/>cash_flow_logs)]
      Aux[(Auxiliary:<br/>face_registrations,<br/>captcha_challenges,<br/>notification_outbox,<br/>settings)]
    end

    subgraph External["External Services"]
      TG[Telegram Bot API]
      WA[Fonnte WhatsApp]
    end

    Client -->|HTTPS + Bearer| Server
    Server <--> DB
    Server -->|sendMessage| TG
    Server -->|send| WA
    TG -->|webhook/polling| Server
    Schedule -.->|cron */1| Server

    style Client fill:#1f1f1f,color:#fbbf24,stroke:#fbbf24
    style Server fill:#0f0f0f,color:#fbbf24,stroke:#fbbf24
    style DB fill:#1f1f1f,color:#fbbf24,stroke:#fbbf24
    style External fill:#1f1f1f,color:#fbbf24,stroke:#fbbf24
```

---

## 13. Frontend Routing Flow

```mermaid
flowchart TD
    Start[User akses /] --> CheckAuth{Token<br/>localStorage?}
    CheckAuth -->|Tidak| Login[Redirect /login]
    CheckAuth -->|Ya| ValidToken{Token valid?<br/>GET /api/auth/me}
    ValidToken -->|Tidak| ClearLogin[Clear token<br/>→ /login]
    ValidToken -->|Ya| RoleCheck{Role?}

    RoleCheck -->|admin| AdminRoutes[Admin Layout +<br/>/admin/*]
    RoleCheck -->|user| UserRoutes[User Layout +<br/>/dashboard, /check-in,<br/>/calendar, etc]

    Login --> LoginForm[Form login + captcha]
    LoginForm --> LoginAPI[POST /api/auth/login]
    LoginAPI -->|Success| StoreToken[Save token + user]
    StoreToken --> RoleCheck

    AdminRoutes --> Pages[7 admin pages]
    UserRoutes --> UPages[7 user pages]

    style Start fill:#fbbf24,color:#000
    style AdminRoutes fill:#0088cc,color:#fff
    style UserRoutes fill:#10b981,color:#000
```

---

## Quick Reference: Tools to Render Diagrams

- **GitHub README** — auto-render Mermaid (sejak 2022)
- **VSCode** — install "Markdown Preview Mermaid Support"
- **Online**: https://mermaid.live/ — paste blok mermaid → instant preview/export PNG/SVG
- **Docusaurus / GitBook** — built-in support
- **Notion** — paste blok ke code block dengan language: mermaid

---

## Diagram Update Process

Setiap kali ada **fitur baru**:
1. Update [DATABASE.md](./DATABASE.md) — kalau ada tabel/relasi baru
2. Update [API.md](./API.md) — kalau ada endpoint baru
3. Update [BACKEND.md](./BACKEND.md) — kalau ada service/observer baru
4. Update [FLOW.md](./FLOW.md) — tambah sequence diagram fitur baru
