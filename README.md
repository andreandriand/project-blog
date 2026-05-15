<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# AndBlog — Laravel 13 Modern Blog

Platform blog modern dengan workflow author/admin, media library, dan AI post generator via Google Gemini.

## Tech Stack

- **PHP** ≥ 8.3
- **Laravel** 13
- **PostgreSQL** (wajib — lihat [Database Requirement](#database-requirement))
- **Frontend**: Blade + Alpine.js 3 + Tailwind CSS 3 + Vite
- **Auth**: Laravel Breeze (Blade stack)
- **AI**: Google Gemini API (`gemini-2.5-flash`)
- **HTML Sanitizer**: `mews/purifier` (preset `blog`)

## Database Requirement

Project ini **membutuhkan PostgreSQL**. SQLite dan MySQL **tidak didukung** karena migration `2024_01_02_000001_update_posts_add_pending_rejected_status.php` memakai raw SQL PostgreSQL (`ALTER TABLE ... CHECK CONSTRAINT` dengan cast `::text`).

Minimal versi PostgreSQL: 13+ (untuk dukungan penuh syntax yang dipakai).

## Quick Start

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Edit .env — pastikan DB_CONNECTION=pgsql dan credentials PostgreSQL sudah benar.
#    Set juga GEMINI_API_KEY jika fitur AI post generator ingin dipakai.

# 4. Database (buat database PostgreSQL dulu)
createdb blog_laravel              # sesuaikan nama dengan DB_DATABASE di .env
php artisan migrate --seed
php artisan storage:link           # untuk akses file upload di /storage/*# 5. Build assets & run
npm run build                      # production build
# atau development:
composer run dev                   # concurrent: serve + queue + pail + vite
```

## Default Credentials (dari seeder)

| Role    | Email               | Password   |
|---------|---------------------|------------|
| admin   | admin@blog.com      | `password` |
| author  | budi@blog.com       | `password` |
| author  | sari@blog.com       | `password` |
| reader  | andi@blog.com       | `password` |

**Ganti password semua user ini sebelum deploy ke environment manapun selain local.**

## Development

```bash
# Serve + queue + vite + pail log viewer (concurrent)
composer run dev

# Tests (saat ini hanya Breeze auth tests)
php artisan test

# Lint
vendor/bin/pint
```

## Project Map

Lihat [`SYSTEM_MAP.md`](SYSTEM_MAP.md) untuk navigasi lengkap: arsitektur, alur request, modul, skema DB, integrasi eksternal, dan catatan keamanan.

## Production Deployment

Untuk panduan deployment lengkap step-by-step di berbagai platform (VPS Ubuntu, Docker, aaPanel di Armbian), lihat [`DEPLOYMENT.md`](DEPLOYMENT.md).

Section di bawah ini cuma ringkasan high-level. Detail lengkap (firewall, SSL, queue worker daemon, backup cron, troubleshooting) ada di file deployment guide.

### Seeder

⚠️ **JANGAN** jalankan `php artisan db:seed` (default `DatabaseSeeder`) di production — ia membuat 4 user dummy dengan password `password`.

Pakai `ProductionSeeder` untuk bootstrap data referensi (categories, tags, settings) **tanpa** user dummy:

```bash
php artisan db:seed --class=ProductionSeeder
```

Lalu buat admin user manual via tinker:

```bash
php artisan tinker
>>> App\Models\User::create([
...     'name' => 'Admin',
...     'email' => 'you@yourdomain.com',
...     'password' => 'STRONG-PASSWORD-HERE',
...     'role' => 'admin',
...     'email_verified_at' => now(),
... ]);
```

### Pre-Deploy Checklist

```
[ ] composer install --no-dev --optimize-autoloader
[ ] npm ci && npm run build
[ ] cp .env.example .env  (lalu isi manual: APP_KEY, DB, GEMINI_API_KEY, MAIL_*, dll)
[ ] APP_ENV=production, APP_DEBUG=false, APP_URL=https://...
[ ] php artisan key:generate
[ ] php artisan storage:link
[ ] createdb blog_laravel && php artisan migrate --force
[ ] php artisan db:seed --class=ProductionSeeder  (BUKAN db:seed biasa)
[ ] php artisan config:cache route:cache view:cache event:cache
[ ] chown -R www-data:www-data storage bootstrap/cache
[ ] Setup queue worker via systemd/supervisor
[ ] Setup pg_dump cron daily ke S3/B2/R2
[ ] curl https://domain/up  → harus return 200
```

### Error Monitoring (Sentry)

Sentry sudah ter-integrate. Untuk aktifkan:

1. Signup gratis di https://sentry.io (5k events/month)
2. Buat project baru tipe "Laravel"
3. Copy DSN, set di `.env` production:

```env
SENTRY_LARAVEL_DSN=https://...@...ingest.sentry.io/...
SENTRY_TRACES_SAMPLE_RATE=0.1     # 10% request di-trace untuk performance monitoring
```

Kalau `SENTRY_LARAVEL_DSN` kosong, Sentry no-op silently — aman untuk local dev.

Test setelah deploy: trigger 500 error sengaja (misal hit route inexistent dengan auth), cek apakah event muncul di dashboard Sentry.

### Mail Provider (Resend)

Project ini sudah include `resend/resend-laravel`. Untuk aktifkan email production:

#### 1. Signup & Verify Domain

1. Signup gratis di https://resend.com (3000 email/bulan, 100/hari)
2. Buka **Domains** → **Add Domain** → masukkan domain Anda (mis. `yourdomain.com`)
3. Resend kasih ~3 DNS record (TXT untuk SPF + DKIM, biasanya MX juga). Tambahkan di DNS provider (Cloudflare/Namecheap/dll)
4. Tunggu propagasi DNS (~5-15 menit), lalu klik **Verify DNS Records** di Resend
5. Status harus jadi `Verified` warna hijau

⚠️ **Tanpa verify domain**, Resend hanya bisa kirim ke alamat email yang sama dengan akun Resend Anda — tidak akan kirim ke user real.

#### 2. Generate API Key

1. **API Keys** → **Create API Key**
2. Permission: **Sending access** (lebih ketat dari Full access)
3. Domain: pilih domain yang sudah verified
4. Copy key yang ke-generate (format `re_...`) — ini hanya muncul sekali

#### 3. Set di `.env` Production

```env
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxxxxxxxxxxxxxxxx
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

`MAIL_FROM_ADDRESS` **harus** memakai domain yang sudah verified di Resend, kalau tidak email akan ditolak.

#### 4. Test Pengiriman Email

Test cepat via tinker setelah deploy:

```bash
php artisan tinker
>>> Mail::raw('Test email dari AndBlog production', function ($m) {
...     $m->to('your-personal@gmail.com')->subject('Resend Test');
... });
```

Kalau sukses tanpa exception, cek inbox tujuan. Kalau gagal, lihat log: `storage/logs/laravel-{date}.log`.

#### 5. Verifikasi Flow Aplikasi

Test 3 flow Breeze yang pakai email:

- **Registration**: register user baru → cek email verifikasi terkirim
- **Password reset**: `/forgot-password` → submit email → cek email reset link
- **Email verification**: dari halaman `/verify-email` → klik "resend verification" → cek email

Semua harus mendarat di inbox real, bukan log file.

#### Local Dev (tetap pakai log)

Untuk local dev, biarkan `MAIL_MAILER=log` di `.env` lokal. Email akan masuk ke `storage/logs/laravel-*.log` tanpa kirim beneran. Cuma production yang pakai Resend.

#### Trouble Shooting

| Issue | Solusi |
|-------|--------|
| `Class "Resend\\Laravel\\..." not found` | `composer install` di server, lalu `php artisan config:clear` |
| Email tidak masuk inbox tapi tidak ada error | Cek **spam folder**, lalu pastikan SPF + DKIM record valid di Resend dashboard |
| `from address ... not authorized` | `MAIL_FROM_ADDRESS` harus match domain yang verified di Resend |
| Rate limit 429 | Free tier: 100/hari. Upgrade ke Pro ($20/bulan, 50k email) |
| Email ditandai phishing | Tambah DMARC record di DNS, gunakan `MAIL_FROM_NAME` yang professional |

## Optimization Backlog

Lihat [`OPTIMIZATION-REPORT.md`](OPTIMIZATION-REPORT.md) untuk daftar rekomendasi perbaikan yang belum dieksekusi (performance, quality, test coverage, CI/CD).

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
