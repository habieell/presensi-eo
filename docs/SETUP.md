# Setup Guide

## Quick Start (Windows + XAMPP)

```cmd
:: 1. Clone / extract project
cd D:\freelance\jomek\Presensi-tsxlaravel-v2

:: 2. Backend
composer install
copy .env.example .env
php artisan key:generate

:: 3. Buat database 'presensi_tsx' di phpMyAdmin
:: 4. Edit .env -> set DB credentials
:: 5. Migrate
php artisan storage:link
php artisan migrate --seed

:: 6. Download face models
php artisan face:download-models

:: 7. Frontend
npm install
npm run build

:: 8. Run
php artisan serve
:: lain terminal:
php artisan queue:listen
php artisan schedule:work
:: lain terminal:
npm run dev
```

## Production Deploy

1. Build assets: `npm run build`
2. Optimize Laravel:
   ```
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan optimize
   ```
3. Set up cron:
   ```cron
   * * * * * cd /path && php artisan schedule:run
   ```
4. Set queue worker (supervisord):
   ```ini
   [program:laravel-worker]
   command=php /path/artisan queue:work --tries=3
   autostart=true
   autorestart=true
   ```

## Environment Variables Penting

```env
APP_URL=https://your-domain.com
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=...
DB_DATABASE=presensi_tsx

# Fonnte WhatsApp
FONNTE_TOKEN=...
ADMIN_WA_NUMBERS=628xxx,628xxx
WHATSAPP_NOTIFICATION_ENABLED=true

# Face
FACE_MATCH_THRESHOLD=0.55
FACE_RECOGNITION_ENABLED=true

# Captcha
CAPTCHA_ENABLED=true
```

## Troubleshooting

### "Failed to load model"
Pastikan `public/face-models/` berisi file model. Run:
```
php artisan face:download-models
```

### Camera permission denied
- Browser butuh HTTPS atau `localhost` untuk akses kamera.
- Cek browser settings → site permissions.

### WhatsApp tidak masuk
- Cek `notification_outbox` table:
  ```sql
  SELECT * FROM notification_outbox ORDER BY created_at DESC LIMIT 10;
  ```
- Status `failed` → cek kolom `response`.
- Pastikan device Fonnte connected.
