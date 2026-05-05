# Dokumentasi Presensi TSX v2

Folder ini berisi dokumentasi teknis lengkap untuk **Backend, Database, API, dan Application Flow**.

## 📚 Index

| File | Isi |
|---|---|
| **[API.md](./API.md)** | Daftar semua endpoint REST API, request/response format, contoh cURL |
| **[DATABASE.md](./DATABASE.md)** | Schema database, ERD (Mermaid), penjelasan tiap tabel, indexing strategy |
| **[BACKEND.md](./BACKEND.md)** | Arsitektur backend Laravel, services, observers, middleware, console commands |
| **[FLOW.md](./FLOW.md)** | 13 diagram flow aplikasi (login, check-in, telegram link, event reminder, dll) |
| **[SETUP.md](./SETUP.md)** | Panduan instalasi (Windows/Linux/production) |
| **[FEATURES.md](./FEATURES.md)** | Daftar lengkap fitur existing & baru |
| **[TREE.md](./TREE.md)** | Visualisasi struktur folder project |

---

## Quick Links

### 🎯 Untuk Developer

- **Mau eksplor API?** → [API.md](./API.md)
- **Mau pahami database?** → [DATABASE.md](./DATABASE.md) — lihat ERD-nya
- **Mau tau flow login/check-in?** → [FLOW.md](./FLOW.md)
- **Mau setup development?** → [SETUP.md](./SETUP.md)
- **Mau tambah fitur baru?** → [BACKEND.md > Adding New Feature Checklist](./BACKEND.md)

### 🎓 Untuk Skripsi / Bab Implementasi

- **Bab 3 (Metodologi)**: pakai [BACKEND.md](./BACKEND.md) untuk arsitektur layered
- **Bab 4 (Implementasi)**:
  - ERD → [DATABASE.md > ERD section](./DATABASE.md)
  - API specification → [API.md](./API.md)
  - Sequence diagram → [FLOW.md](./FLOW.md)
- **Lampiran**: [FEATURES.md](./FEATURES.md) untuk daftar fitur

### 👨‍💼 Untuk Stakeholder

- **Apa aja fiturnya?** → [FEATURES.md](./FEATURES.md)
- **Cara setup?** → [SETUP.md](./SETUP.md)
- **Cara kerjanya?** → [FLOW.md](./FLOW.md) — diagram visual

---

## Render Diagram Mermaid

Semua diagram di file `.md` ini pakai **Mermaid syntax**. Cara nampilinnya:

| Tools | Cara |
|---|---|
| **GitHub** | Auto-render saat browse online ✅ |
| **VSCode** | Install ext: "Markdown Preview Mermaid Support" |
| **VSCode Built-in Preview** | Ctrl+Shift+V (kalau ada plugin Mermaid) |
| **Online Editor** | Copy block → paste ke https://mermaid.live |
| **PDF Export** | Pakai mermaid.live → "Actions" → Export PNG/SVG |

---

## Kontribusi Dokumentasi

Setiap fitur/perubahan baru, **wajib update** dokumentasi terkait:

- Endpoint baru → tambah di [API.md](./API.md)
- Tabel/kolom baru → update [DATABASE.md](./DATABASE.md)
- Service/observer baru → update [BACKEND.md](./BACKEND.md)
- Flow baru → tambah sequence di [FLOW.md](./FLOW.md)

Format: pakai Bahasa Indonesia formal + emoji minimal (cuma untuk navigasi visual).
