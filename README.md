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
php artisan storage:link           # untuk akses file upload di /storage/*

# 5. Build assets & run
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

## Optimization Backlog

Lihat [`OPTIMIZATION-REPORT.md`](OPTIMIZATION-REPORT.md) untuk daftar rekomendasi perbaikan yang belum dieksekusi (performance, quality, test coverage, CI/CD).

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
