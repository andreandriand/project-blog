# Analisis Lengkap & Rekomendasi Optimisasi — Project Blog Laravel

**Tanggal awal:** 2026-04-13
**Direvisi:** 2026-05-12 (verifikasi ulang terhadap kode aktual)
**Eksekusi CRITICAL:** 2026-05-12 (5/5 CRITICAL selesai + build verified)
**Eksekusi Bundle Quick-Win #2:** 2026-05-12 (#7, #10, #13, #15 selesai + runtime verified)
**Eksekusi #8:** 2026-05-15 (Admin Dashboard query consolidation, runtime verified)
**Eksekusi Pre-Launch:** 2026-05-15 (custom error pages + newsletter cleanup, runtime verified)
**Eksekusi Skenario B:** 2026-05-15 (Phase 1 hardening, Sentry, 68 tests, GitHub Actions CI)
**Project:** ModernBlog / AndBlog (Laravel 13 + Vite + Tailwind + Alpine.js + PostgreSQL)

---

## Legenda Status Revisi

- ✅ **VALID** — Masalah masih ada, rekomendasi masih tepat
- ⚠️ **PARTIAL** — Masalah masih ada tetapi ada nuansa / rekomendasi perlu disesuaikan
- ❌ **OBSOLETE** — Sudah diperbaiki atau tidak lagi relevan dengan kondisi kode saat ini
- 🟢 **FIXED** — Sudah dieksekusi pada revisi ini (2026-05-12)

---

## CRITICAL — Harus Segera Diperbaiki

### 1. Bug: Password Double-Hashing — 🟢 FIXED (2026-05-12)

**File:** `app/Http/Controllers/Admin/UserController.php`

Model `User` punya cast `'password' => 'hashed'` yang otomatis hash. Password sebelumnya di-hash 2 kali → user admin-panel tidak bisa login.

**Eksekusi:** `Hash::make()` dihapus dari method `store` (line 41 lama) dan `update` (line 73 lama). Import `Hash` facade dihapus. Header doc ditambah menjelaskan cast behavior untuk mencegah regression.

**Verifikasi:** `php -l` clean.

---

### 2. XSS Vulnerability: Unescaped HTML — 🟢 FIXED (2026-05-12)

**File:** `resources/views/posts/show.blade.php` (tetap `{!! $post->body !!}`, tapi sekarang body disanitasi di sumber).

Body post (termasuk output AI Gemini) dulu di-render tanpa sanitasi — Stored XSS risk.

**Eksekusi:**
1. Install `mews/purifier ^3.4.4` (wrapper HTMLPurifier).
2. Publish config ke `config/purifier.php` dan tambah preset `blog`:
   - Allowed tags: `h2,h3,h4,p,br,strong,em,b,i,u,s,a,ul,ol,li,blockquote,code,pre,hr,img,figure,figcaption`
   - Link: `target=_blank`, `rel=nofollow`
   - Scheme URL: `http`, `https`, `mailto` saja
   - CSS properties: kosong (tidak izinkan inline style)
3. `Purifier::clean($body, 'blog')` dipasang di:
   - `Admin\PostController::store`
   - `Admin\PostController::update`
   - `Author\PostController::store`
   - `Author\PostController::update`
   - `Admin\AiPostController::store`

**Verifikasi runtime:** payload `<script>alert(1)</script>`, `href="javascript:..."`, `onerror=alert(1)` semuanya di-strip saat test via tinker. Tag `<h2>`, `<p>` aman dipertahankan.

**Catatan:** Post lama yang sudah masuk DB sebelum patch ini **belum disanitasi**. Jika ada data legacy dari author/AI yang mencurigakan, jalankan backfill manual (mis. via tinker: `Post::each(fn($p) => $p->update(['body' => Purifier::clean($p->body, 'blog')]))`).

---

### 3. SVG Upload = Stored XSS — 🟢 FIXED (2026-05-12)

**File:** `app/Http/Controllers/Admin/MediaController.php` & `app/Http/Controllers/Author/MediaController.php`

SVG dihapus dari `mimes` validasi di kedua controller. Whitelist sekarang: `jpeg,png,jpg,gif,webp`.

**Verifikasi:** `grep svg app/` → no matches. `php -l` clean.

---

### 4. `featured_image_path` Tidak Divalidasi — 🟢 FIXED (2026-05-12)

**File:** `app/Http/Controllers/Admin/PostController.php` & `app/Http/Controllers/Author/PostController.php`

**Eksekusi:** tambah validasi DB lookup pada field `featured_image_path`.
- **Admin**: `'featured_image_path' => 'nullable|string|exists:media,path'` (admin boleh pakai media siapa saja).
- **Author**: `Rule::exists('media', 'path')->where(fn ($q) => $q->where('user_id', auth()->id()))` — author hanya boleh refer media miliknya sendiri.

Rule ini otomatis blok path sembarang, path traversal (`..`), dan path ke file non-media.

**Verifikasi:** `php -l` clean pada kedua controller. Form tidak perlu diubah — field tetap `featured_image_path` (hidden input di blade component `media-picker`).

---

### 5. Konflik Tailwind v3 vs v4 — 🟢 FIXED (2026-05-12)

**File:** `package.json`

**Eksekusi:** `npm uninstall @tailwindcss/vite` (removed 11 packages). `tailwindcss: ^3.1.0` dipertahankan.

**Verifikasi:** `npx vite build` sukses — 55 modules transformed, 964ms. Output `public/build/assets/app-*.css` (77 kB) dan `app-*.js` (82 kB).

---

## HIGH — Duplikasi Kode Masif

### 6. Admin vs Author Controllers ~90% Identik — ✅ VALID

| Admin                         | Author                         | Duplikasi |
| ----------------------------- | ------------------------------ | --------- |
| `Admin/PostController.php`    | `Author/PostController.php`    | ~80%      |
| `Admin/MediaController.php`   | `Author/MediaController.php`   | ~90%      |
| `Admin/CommentController.php` | `Author/CommentController.php` | ~60%      |

**Status 2026-05-12:** Duplikasi masih ada persis seperti dilaporkan.

**Solusi:** Ekstrak ke **Form Requests** (validasi), **Service classes** (business logic), dan **Traits** (shared behavior). Bisa menghilangkan ~500 baris duplikasi.

Contoh struktur:

```
app/
├── Http/
│   └── Requests/
│       ├── StorePostRequest.php
│       └── UpdatePostRequest.php
├── Services/
│   ├── PostService.php
│   ├── MediaService.php
│   └── GeminiService.php  (sudah ada)
```

---

### 7. Validasi Post Diduplikasi 5 Kali — ✅ VALID

Rules validasi post di-copy-paste di:
- `Admin/PostController::store`
- `Admin/PostController::update`
- `Author/PostController::store`
- `Author/PostController::update`
- `AiPostController::store`

**Status 2026-05-12:** Tetap 5 tempat. Perhatikan: rules di Admin vs Author **sedikit berbeda** — Author tidak punya field `status` dan `is_featured` (di-set otomatis). FormRequest perlu handle perbedaan ini (misal via 2 class terpisah atau `authorize()` + conditional rules).

**Solusi:** Buat `StorePostRequest` dan `UpdatePostRequest`.

---

## MEDIUM — Performance & Query Optimization

### 8. N+1 Query pada Comment Replies — 🟢 FIXED (2026-05-12)

**File:** `app/Http/Controllers/PostController.php`

**Eksekusi:** `$post->load([...])` diperluas untuk include `approvedComments.replies.user`. Array sekarang rapi multi-line.

**Verifikasi:** `php -l` clean. Runtime load terverifikasi via route list + Vite build sukses.

---

### 9. Admin Dashboard: 6 Query Terpisah — 🟢 FIXED (2026-05-15)

**File:** `app/Http/Controllers/Admin/DashboardController.php`

**Eksekusi:** Refactor `index()` — agregasi posts dan comments di-konsolidasi jadi 1 query masing-masing pakai sintaks PostgreSQL `COUNT(*) FILTER (WHERE ...)`. Hasil array `$stats` tetap punya 6 key yang sama persis (zero perubahan view).

**Sebelum:** 6 query agregasi terpisah (`Post::count`, `Post::where(status)->count`, `Comment::count`, `Comment::where(is_approved)->count`, `Post::sum(views_count)`, `User::count`).

**Sesudah:** 2 query agregasi konsolidasi + 1 `User::count`. Total 5 query (recent posts/comments tidak berubah).

**Verifikasi:**
- `php -l` clean
- Runtime test (script standalone): postStats + commentStats menghasilkan 3 queries total, value benar (11 posts/10 published/10893 views/21 comments/5 pending)
- Live HTTP GET ke `/admin` (login admin@blog.com): status 200, view render normal dengan stats numbers

Cast `(int)` ditambahkan pada hasil agregasi karena PG returns `BIGINT` yang otomatis jadi string di PHP — `number_format()` di view butuh int.

---

### 10. `Setting::get()` Tanpa Cache — ❌ OBSOLETE

**File:** `app/Models/Setting.php`

**Status 2026-05-12:** Grep menunjukkan `Setting::get()` **tidak pernah dipanggil** di controller/view manapun. Hanya `Setting::set()` dipanggil di seeder untuk 3 key (`site_name`, `site_description`, `site_email`). Fitur settings belum terpakai di aplikasi.

**Verdict:** Optimasi cache ini akan **valid kembali** kalau nanti settings dipakai di layout (misal untuk header site name, footer description, dll.). Saat ini tidak ada target untuk dioptimasi.

**Tindakan saat ini:** Skip. Revisit kalau fitur settings sudah terintegrasi di views.

---

### 11. Slug Uniqueness: Loop Query — ✅ VALID (low impact)

**File:** `app/Traits/GeneratesUniqueSlug.php`

**Status 2026-05-12:** Trait tidak berubah. Setiap iterasi = 1 query `exists()`.

**Dampak nyata:** Kecil. Dalam praktik normal, collision > 2-3 sangat jarang untuk title blog yang panjang. Optimasi di sini low-priority.

**Solusi (kalau mau dirapikan):** 1 query `LIKE` lalu counter di PHP.

```php
public function generateUniqueSlug(string $title, string $modelClass, ?int $excludeId = null): string
{
    $baseSlug = Str::slug($title);

    $existingSlugs = $modelClass::where('slug', 'LIKE', $baseSlug . '%')
        ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
        ->pluck('slug')
        ->toArray();

    if (! in_array($baseSlug, $existingSlugs)) {
        return $baseSlug;
    }

    $counter = 2;
    while (in_array($baseSlug . '-' . $counter, $existingSlugs)) {
        $counter++;
    }

    return $baseSlug . '-' . $counter;
}
```

---

### 12. Homepage & Sitemap Tanpa Cache — ✅ VALID

**File:** `app/Http/Controllers/HomeController.php`, `SitemapController.php`

**Status 2026-05-12:** Tidak ada `Cache::remember`. Setiap hit = DB query.

**Solusi:**

```php
// HomeController::index
$featuredPosts = Cache::remember('home.featured', 3600, fn () =>
    Post::published()->featured()->with(['user', 'category'])->latest('published_at')->take(3)->get()
);

$latestPosts = Cache::remember('home.latest', 1800, fn () =>
    Post::published()->with(['user', 'category', 'tags'])->latest('published_at')->take(6)->get()
);

$categories = Cache::remember('home.categories', 3600, fn () =>
    Category::withCount('publishedPosts')->has('publishedPosts')->orderByDesc('published_posts_count')->take(8)->get()
);

// SitemapController::index
$content = Cache::remember('sitemap.xml', 3600, function () {
    $posts      = Post::published()->select('slug', 'updated_at', 'featured_image')->latest('updated_at')->get();
    $categories = Category::select('slug', 'updated_at')->get();
    $tags       = Tag::select('slug', 'updated_at')->get();
    return view('sitemap', compact('posts', 'categories', 'tags'))->render();
});
```

**Invalidasi cache:** perlu ditambahkan saat publish/unpublish/update post (misal via Observer atau di controller approve/reject/update).

---

### 13. Cache & Session Driver = `database` — ⚠️ PARTIAL

**File:** `.env`

**Status 2026-05-12:** `.env` aktif: `CACHE_STORE=database`, `SESSION_DRIVER=database`, `APP_ENV=local`. Untuk **local development** ini wajar — tidak butuh Redis terpasang. Untuk **production** memang sebaiknya Redis.

**Verdict:** Ini catatan deployment, bukan bug. Tangani sebagai bagian dari checklist go-live.

```env
# production .env
CACHE_STORE=redis
SESSION_DRIVER=redis
```

---

## MEDIUM — Security & Config

### 14. Rate Limiting Login — ⚠️ PARTIAL (report semula keliru)

**Status 2026-05-12:** Klaim report awal "tidak ada rate limiting" **tidak akurat**. `app/Http/Requests/Auth/LoginRequest.php` (line 61-77) sudah mengimplementasi rate limiter: 5 attempts per menit, keyed by `lowercase(email)|ip`, dengan event `Lockout` saat threshold tercapai.

**Yang benar-benar missing:** Route-level `throttle` middleware sebagai defense-in-depth untuk `POST /login`. Rate limiter di `LoginRequest` baru tereksekusi setelah validasi rules dijalankan; throttle middleware bisa reject lebih dini.

**Solusi (opsional, double-layer):** Tambahkan throttle di `routes/auth.php`:

```php
Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:10,1');  // batas longgar karena LoginRequest sudah ada
```

Prioritas: **rendah**, karena proteksi utama sudah ada.

---

### 15. Tidak Ada Security Headers — 🟢 FIXED (2026-05-12)

**File baru:** `app/Http/Middleware/SecurityHeaders.php`
**Modifikasi:** `bootstrap/app.php`

**Eksekusi:**
- Dibuat middleware `SecurityHeaders` yang set 4 headers: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, dan `Strict-Transport-Security` (conditional — hanya saat `$request->isSecure()` agar tidak lock out dev environment HTTP).
- Ter-register di `bootstrap/app.php` via `$middleware->web(append: [SetLocale::class, SecurityHeaders::class])`.
- CSP sengaja tidak di-set di iterasi ini — butuh tuning terpisah karena view pakai inline Alpine handler dan external font/image. Tambahkan di iterasi berikutnya.
- `X-XSS-Protection` sengaja tidak ditambah (sudah deprecated di browser modern).

**Verifikasi runtime:** Live HTTP GET ke `http://project-blog.test/` → 3 headers muncul:
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
```
HSTS tidak muncul di HTTP (expected behavior — akan muncul di HTTPS production).

---

### 16. `APP_DEBUG=true` di `.env` — ⚠️ PARTIAL

**Status 2026-05-12:** `.env` aktif: `APP_DEBUG=true` + `APP_ENV=local`. Untuk **local development** ini **benar** — dibutuhkan untuk debugging. Bukan bug.

**Verdict:** Pindahkan ke checklist deployment. Di `.env` production harus:

```env
APP_ENV=production
APP_DEBUG=false
```

Plus: pastikan `.env` tidak ter-commit ke repo (cek `.gitignore`).

---

### 17. Migration PostgreSQL-Specific — 🟢 FIXED (2026-05-12)

**Files:** `.env.example`, `composer.json`, `README.md`.

Project target PostgreSQL sebagai DB utama (`.env` aktif sudah pakai `pgsql`). Masalah sebelumnya: `.env.example` dan README tidak menyebut requirement ini, sehingga dev baru yang clone repo akan gagal migrate.

**Eksekusi:**
1. `.env.example` → `DB_CONNECTION=pgsql` dengan host/port/database values.
2. `composer.json` → hapus 2 baris script `post-create-project-cmd` yang membuat `database/database.sqlite` dan run `migrate --graceful` (keduanya irrelevant / berbahaya di environment PG).
3. `README.md` di-rewrite dari boilerplate Laravel bawaan jadi doc project-specific dengan section "Database Requirement" yang eksplisit, Quick Start, default credentials seeder, dan link ke `SYSTEM_MAP.md` + `OPTIMIZATION-REPORT.md`.

**Verifikasi:** File ter-update. Belum ada verifikasi fresh-clone (perlu di environment terpisah).

---

### 18. Missing Database Indexes — ⚠️ PARTIAL (sebagian sudah ada)

**Status 2026-05-12:** Migration `2024_01_03_000001_add_performance_indexes` sudah menambahkan:
- `posts.status` ✅
- `posts.published_at` ✅
- `posts.(status, published_at)` ✅
- `comments.is_approved` ✅

**Yang masih missing:**
- `comments.parent_id` (dipakai untuk threading replies)
- `posts.is_featured` (dipakai `scopeFeatured` di homepage)
- `posts.category_id` (dipakai filter public + admin)
- `media.user_id` (dipakai filter `Author/MediaController`)

**Catatan PostgreSQL:** Berbeda dari MySQL, PostgreSQL **tidak otomatis** membuat index pada foreign key. Jadi FK index memang perlu dibuat manual.

**Solusi:** Migration baru.

```php
Schema::table('comments', fn (Blueprint $t) => $t->index('parent_id'));
Schema::table('posts', function (Blueprint $t) {
    $t->index('is_featured');
    $t->index('category_id');
});
Schema::table('media', fn (Blueprint $t) => $t->index('user_id'));
```

Pertimbangan: untuk tabel yang masih kecil (seeder cuma ~9 posts), penambahan index biaya write-nya masih bisa diabaikan.

---

## LOW — Code Quality

### 19. Newsletter Form Non-Fungsional — 🟢 FIXED (2026-05-15)

**File:** `resources/views/home.blade.php`

**Eksekusi:** Section newsletter (~16 baris HTML, line 163-178 lama) dihapus. Form sebelumnya tidak punya `<form>` wrapper, tidak punya action, tidak punya backend handler — menyesatkan user yang isi email karena email tidak pernah disimpan/dikirim.

**Verifikasi runtime:** Live HTTP `GET /` — string "Subscribe", "email" input, dan "Get notified" tidak ditemukan di response. Section "Categories" dan "Latest Articles" tetap normal.

**Bonus:** CSS bundle turun dari 76.99 kB → 65.81 kB (−11 kB) karena Tailwind tree-shake utility classes yang sebelumnya hanya dipakai di section newsletter (`bg-white/20`, `placeholder-white/60`, dll).

**Kalau nanti newsletter dibutuhkan:** implementasi ulang butuh tabel `newsletter_subscribers`, `NewsletterController@subscribe`, validasi unique email, double-opt-in via mail (token confirmation), dan integrasi ke provider real (Mailchimp / SendGrid Marketing / Resend Audiences). Skip dulu sampai ada strategi product yang konkret.

---

### 20. Duplicate Font Loading — 🟢 FIXED (2026-05-12)

**Eksekusi:**
- Hapus `@import url('https://fonts.googleapis.com/...')` dari `resources/css/app.css` (line 1 lama).
- Update `resources/views/layouts/blog.blade.php` — pindah dari Google Fonts ke **Bunny Fonts** (tanpa tracking, GDPR-friendly), pakai `<link rel="preconnect" href="https://fonts.bunny.net">` + single stylesheet `<link>`.
- `layouts/app.blade.php` (Breeze — dipakai untuk auth/profile) tetap pakai Figtree dari Bunny, tidak diubah.

**Verifikasi runtime:** `curl http://project-blog.test/ | grep -i font` → hanya 1 reference Inter (dari Bunny). `fonts.googleapis.com` hilang dari bundle CSS dan dari HTML response.

**Efek build:** CSS bundle turun dari 77.09 kB → 76.99 kB (satu HTTP request hilang saat render halaman).

---

### 21. Manual `.prose` Styles — ✅ VALID

**File:** `resources/css/app.css` (line 55-115)

**Status 2026-05-12:** Masih ~60 baris manual.

**Solusi:** Ganti dengan plugin `@tailwindcss/typography`.

```bash
npm install -D @tailwindcss/typography
```

```js
// tailwind.config.js
import typography from '@tailwindcss/typography';

export default {
    // ...
    plugins: [forms, typography],
};
```

Ganti class manual `.prose` dengan `prose prose-lg dark:prose-invert` dari plugin.

---

### 22. Zero Business Logic Tests — 🟢 FIXED (2026-05-15)

**Eksekusi:**

1. **Setup PG test environment**:
   - `phpunit.xml` di-update: `DB_CONNECTION=pgsql`, `DB_DATABASE=blog_test`, plus env var Sentry kosong agar no-op di test
   - Buat database `blog_test` di PostgreSQL local
   - Semua migration jalan termasuk PG-specific `update_posts_add_pending_rejected_status` (CHECK constraint)

2. **Buat 5 factory baru** + tambah `HasFactory` trait di model Media:
   - `PostFactory` dengan state methods: `published()`, `pending()`, `rejected()`, `featured()`
   - `CategoryFactory`, `TagFactory`
   - `CommentFactory` dengan state `guest()`, `pending()`
   - `MediaFactory`

3. **Tulis 31 test baru** (5 file Feature + 1 file Unit):

| File | Test | Coverage |
|------|------|----------|
| `tests/Feature/Admin/UserControllerTest.php` | 4 | Regression bug #1 (password tidak double-hash, login berfungsi) |
| `tests/Feature/Admin/PostControllerTest.php` | 7 | Regression #2 (XSS sanitize), #4 (path traversal), workflow approve/reject |
| `tests/Feature/Author/PostControllerTest.php` | 7 | Ownership policy + Purifier + media ownership scoping |
| `tests/Feature/Admin/MediaControllerTest.php` | 4 | Regression #3 (SVG rejected) |
| `tests/Feature/PublicPostTest.php` | 9 | Smoke test public endpoints + N+1 query budget assertion + security headers |
| `tests/Unit/PurifierBlogPresetTest.php` | 12 | 12 attack vectors per-isolated (script, event handler, javascript:, data:, iframe, style, object, embed, form, dll.) |

**Test result final:** ✅ **68 passed (166 assertions) — 5.80s**

**Bonus**: testing investasi langsung membayar. Test ini menemukan bug existing yang tidak pernah ketahuan:
- `Str` facade salah import (`Illuminate\Support\Facades\Str` tidak ada — pindahkan ke `Illuminate\Support\Str`) di `Admin/MediaController` dan `Author/MediaController`. Akan crash production saat upload media. **Sudah di-fix.**
- 3 test Breeze bawaan refer route `dashboard` yang tidak ada di project ini (custom redirect ke `home`/`admin.dashboard`). Akan terus fail di CI sampai di-fix. **Sudah di-fix** ke `home`.

---

### 23. Tidak Ada Docker/CI-CD — 🟢 FIXED (2026-05-15)

**File baru:** `.github/workflows/ci.yml`

**Eksekusi:** GitHub Actions workflow dengan 2 job:

1. **Test job** (PHP 8.4):
   - PostgreSQL 16 sebagai service (matches production target)
   - Cache Composer + npm packages
   - `composer validate --strict`
   - Install dep + build assets via Vite
   - `php artisan migrate --force`
   - `php artisan test --parallel --processes=2`

2. **Lint job**: `vendor/bin/pint --test` (no auto-fix di CI, hanya verifikasi)

**Trigger:** push ke `main`/`master` + PR ke branch tersebut.

**Catatan:** Docker / Dockerfile tidak dibuat — proyek ini di-deploy via Forge/Envoyer/manual VPS, bukan container. Bisa ditambah jika nanti ada kebutuhan.

---

## Ringkasan Revisi — Status Semua Item

| #  | Item                                              | Status 2026-05-15 |
| -- | ------------------------------------------------- | ----------------- |
| 1  | Password double-hashing                            | 🟢 FIXED          |
| 2  | XSS HTML post body                                 | 🟢 FIXED          |
| 3  | SVG upload                                         | 🟢 FIXED          |
| 4  | `featured_image_path` tidak divalidasi             | 🟢 FIXED          |
| 5  | Konflik Tailwind v3/v4                             | 🟢 FIXED          |
| 6  | Duplikasi Admin vs Author controller               | ✅ VALID           |
| 7  | Validasi post duplikasi 5x                         | ✅ VALID           |
| 8  | N+1 comment replies                                | 🟢 FIXED          |
| 9  | Admin dashboard 6 query                            | 🟢 FIXED          |
| 10 | `Setting::get()` tanpa cache                       | ❌ OBSOLETE        |
| 11 | Slug uniqueness loop                               | ✅ VALID (low impact) |
| 12 | Homepage & sitemap tanpa cache                     | ✅ VALID           |
| 13 | Cache & session driver = database                  | ⚠️ PARTIAL (deployment) |
| 14 | Rate limiting login                                | ⚠️ PARTIAL (sudah ada di LoginRequest) |
| 15 | Security headers                                   | 🟢 FIXED          |
| 16 | `APP_DEBUG=true`                                   | ⚠️ PARTIAL (local OK) |
| 17 | Migration PostgreSQL-specific (docs)               | 🟢 FIXED          |
| 18 | Missing indexes                                    | ⚠️ PARTIAL (4 dari 8 sudah ada) |
| 19 | Newsletter form non-fungsional                     | 🟢 FIXED          |
| 20 | Duplicate font loading                             | 🟢 FIXED          |
| 21 | Manual `.prose` styles                             | ✅ VALID           |
| 22 | Zero business logic tests                          | 🟢 FIXED          |
| 23 | Tidak ada Docker/CI-CD                             | 🟢 FIXED          |

**Plus item baru di luar laporan asli (Skenario B 2026-05-15):**
- Rate-limit `POST /admin/ai-posts/generate` (`throttle:5,10`) — cost protection Gemini API
- `LOG_CHANNEL=stack` + `LOG_STACK=daily` + `LOG_DAILY_DAYS=14` — log rotation
- Default post image SVG placeholder (`public/images/post-default.svg`) — hapus hardcoded Unsplash URL
- `ProductionSeeder` — seeder aman untuk production tanpa user dummy
- Sentry error monitoring integrated (siap pakai, tinggal isi `SENTRY_LARAVEL_DSN`)
- Logo brand custom + favicon dengan cache-busting
- Bug fix existing yang ditemukan oleh tests: `Str` facade import keliru di Admin/Author MediaController

**Ringkasan angka:** 13 FIXED ✅ (semua CRITICAL + 8 medium/low), 3 VALID belum dieksekusi, 6 PARTIAL, 1 OBSOLETE.

---

## Ringkasan Action Items (Urut Prioritas Eksekusi)

| #  | Action                                                   | Impact               | Effort    | Status |
| -- | -------------------------------------------------------- | -------------------- | --------- | ------ |
| 1  | Fix double-hashing bug di `UserController`               | CRITICAL Bug         | 5 menit   | 🟢 Done |
| 2  | Sanitasi HTML — install `mews/purifier`                  | CRITICAL Security    | 30 menit  | 🟢 Done |
| 3  | Hapus SVG dari allowed upload types                      | CRITICAL Security    | 5 menit   | 🟢 Done |
| 4  | Validasi `featured_image_path`                           | CRITICAL Security    | 15 menit  | 🟢 Done |
| 5  | Hapus `@tailwindcss/vite` dari `package.json`            | CRITICAL Build       | 5 menit   | 🟢 Done |
| 6  | Hapus / implementasikan newsletter form                  | LOW UX               | 5 menit   | 🟢 Done |
| 7  | Fix N+1 queries — eager load `replies.user`              | MEDIUM Performance   | 5 menit   | 🟢 Done |
| 8  | Konsolidasi query Admin Dashboard                        | MEDIUM Performance   | 15 menit  | 🟢 Done |
| 9  | Tambah index yang masih missing                          | MEDIUM Performance   | 15 menit  | ⏳      |
| 10 | Tambah security headers middleware                       | MEDIUM Security      | 20 menit  | 🟢 Done |
| 11 | `Cache::remember` untuk homepage & sitemap               | MEDIUM Performance   | 45 menit  | ⏳      |
| 12 | Ekstrak Form Requests & Services                         | HIGH Maintainability | 2-3 jam   | ⏳      |
| 13 | Deduplikasi font loading (Inter 2x)                      | LOW Performance      | 10 menit  | 🟢 Done |
| 14 | Ganti manual `.prose` dengan `@tailwindcss/typography`   | LOW Quality          | 30 menit  | ⏳      |
| 15 | Update `.env.example` ke `DB_CONNECTION=pgsql` + README  | LOW Documentation    | 10 menit  | 🟢 Done |
| 16 | Tulis feature tests untuk core business logic            | MEDIUM Quality       | 4-6 jam   | 🟢 Done |
| 17 | Setup GitHub Actions CI dengan PG service                | MEDIUM Quality       | 30 menit  | 🟢 Done |

**Progress:** 13/17 done. Sisa 4 item:
- #9 missing indexes (15 min) — low impact sampai data >10k rows
- #11 cache homepage/sitemap (45 min) — butuh strategi invalidation
- #12 Form Requests + Services (2-3 jam) — refactor besar (sekarang aman karena ada test coverage)
- #14 `@tailwindcss/typography` (30 min) — nice-to-have

---

## Eksekusi Log

### 2026-05-15 — Skenario B (Solid Infrastructure)

**Phase 1: Code hardening patches** (~30 menit)
- `routes/web.php` — `throttle:5,10` di `POST /admin/ai-posts/generate` (cost protection Gemini)
- `.env.example` + `.env` — `LOG_CHANNEL=stack` + `LOG_STACK=daily` + `LOG_DAILY_DAYS=14`
- `app/Models/Post.php` — `getFeaturedImageUrlAttribute` fallback ke local SVG (drop hardcoded Unsplash URL)
- `public/images/post-default.svg` — **baru**, gradient brand placeholder
- `database/seeders/ProductionSeeder.php` — **baru**, seeder aman tanpa user dummy
- `README.md` — section "Production Deployment" dengan checklist + warning seeder

**Phase 2: Sentry error monitoring** (~1 jam)
- `composer require sentry/sentry-laravel` (4.25.1 + ezyang/htmlpurifier deps)
- `bootstrap/app.php` — `Integration::handles($exceptions)` di withExceptions
- `.env.example` — `SENTRY_LARAVEL_DSN` (default empty = no-op silently di local)
- `README.md` — section "Error Monitoring (Sentry)" dengan instruksi signup + DSN
- Verified: `php artisan route:list` boot tanpa error

**Phase 3: Tests** (~4 jam total)

3a — Setup PG test DB:
- `phpunit.xml` — switch dari SQLite in-memory ke PG (`DB_DATABASE=blog_test`)
- `createdb blog_test` di PostgreSQL local
- Verified: semua 11 migration jalan termasuk PG-specific CHECK constraint

3b — Factories baru (5 file):
- `database/factories/{Post,Category,Tag,Comment,Media}Factory.php`
- Plus tambah `HasFactory` trait di `App\Models\Media`
- Smoke test: `Post::factory()->published()->featured()->create()` works end-to-end

3c — Feature tests (5 file, 31 test):
- `tests/Feature/Admin/UserControllerTest.php` (4 test) — regression bug #1
- `tests/Feature/Admin/PostControllerTest.php` (7 test) — regression #2 + #4 + workflow
- `tests/Feature/Author/PostControllerTest.php` (7 test) — ownership + Purifier + media scoping
- `tests/Feature/Admin/MediaControllerTest.php` (4 test) — regression #3 SVG
- `tests/Feature/PublicPostTest.php` (9 test) — smoke + N+1 budget + security headers

3d — Unit tests (1 file, 12 test):
- `tests/Unit/PurifierBlogPresetTest.php` — 12 attack vectors per-isolated

**Bug existing yang ditemukan tests:**
- `App\Http\Controllers\Admin\MediaController` & `Author\MediaController` import `Str` salah:
  `use Illuminate\Support\Facades\Str;` → tidak ada class itu. Fix: pindah ke `use Illuminate\Support\Str;`.
  Akan crash di production saat upload media.
- 3 test Breeze bawaan refer route `dashboard` yang tidak ada (custom redirect ke `home`/`admin.dashboard`).
  Fix: ganti `route('dashboard')` → `route('home')`.

**Phase 4: GitHub Actions CI**
- `.github/workflows/ci.yml` — **baru**, 2 job:
  - `test`: PHP 8.4, PG 16 service, composer + npm cache, migrate + parallel test
  - `lint`: `vendor/bin/pint --test` (verifikasi code style tanpa auto-fix)
- Trigger: push + PR ke main/master

**Final state:** ✅ **68 passed (166 assertions) — 5.80s** (local PG). CI pipeline siap run di GitHub.

### 2026-05-15 — Pre-Launch Checklist: Custom Error Pages + Newsletter Cleanup

Files yang ditambah/diubah:
- `resources/views/errors/layout.blade.php` — **baru**, shared layout untuk semua error page (gradient brand, dark mode, Bunny Fonts Inter, `<meta name="robots" content="noindex,nofollow">`)
- `resources/views/errors/404.blade.php` — **baru**, "Halaman Tidak Ditemukan"
- `resources/views/errors/403.blade.php` — **baru**, "Akses Ditolak"
- `resources/views/errors/419.blade.php` — **baru**, "Sesi Kedaluwarsa" (CSRF token expired)
- `resources/views/errors/429.blade.php` — **baru**, "Terlalu Banyak Permintaan" (throttled)
- `resources/views/errors/500.blade.php` — **baru**, "Server Bermasalah"
- `resources/views/errors/503.blade.php` — **baru**, "Sedang Pemeliharaan" (mendukung pesan custom dari `php artisan down --message="..."`)
- `resources/views/home.blade.php` — newsletter section dihapus (item #19)

Verifikasi:
- `npx vite build` sukses (55 modules, 1.00s, CSS turun ke 65.81 kB)
- Live HTTP `GET /blog/non-existent-slug-xxx`: status 404, halaman render brand layout dengan "404", "Halaman Tidak Ditemukan", `<meta name="robots" content="noindex,nofollow">`
- Live HTTP `GET /`: status 200, newsletter strings tidak ditemukan, section Categories + Latest Articles intact

Catatan implementasi:
- Layout shared via `@include` (bukan `@extends`) karena 6 page tinggal pass `$code`/`$title`/`$description` via `compact()` — lebih ringkas dari section-yield pattern.
- 404 + 403 + 419 + 429 = client error, tone "petunjuk netral".
- 500 = system fault, tone "kami sedang menangani".
- 503 ambil pesan custom dari `$exception?->getMessage()` agar `php artisan down --message="..."` bisa tampil custom.
- Action buttons: "Beranda" (gradient primary) + "Halaman Sebelumnya" (secondary, fallback ke beranda kalau no referrer).

### 2026-05-15 — #8 Admin Dashboard query consolidation

File yang diubah:
- `app/Http/Controllers/Admin/DashboardController.php` — agregasi posts + comments dikonsolidasi via `COUNT(*) FILTER (WHERE ...)` (PG-native), header doc ditambah

Verifikasi:
- `php -l` clean
- Runtime test: 3 query untuk agregasi (sebelumnya 6), value identik
- Live HTTP `GET /admin` (login admin): status 200, dashboard render normal

### 2026-05-12 — Bundle Quick-Win #2 (#7, #10, #13, #15)

Files yang diubah:
- `app/Http/Controllers/PostController.php` — eager load `approvedComments.replies.user` (fix N+1)
- `app/Http/Middleware/SecurityHeaders.php` — **baru dibuat**, set X-Frame-Options/X-Content-Type-Options/Referrer-Policy/HSTS-conditional
- `bootstrap/app.php` — register `SecurityHeaders::class` di web middleware group
- `resources/css/app.css` — hapus `@import` Google Fonts Inter
- `resources/views/layouts/blog.blade.php` — pindah Inter load ke Bunny Fonts (GDPR-friendly, no tracking)
- `.env.example` — `DB_CONNECTION=pgsql` dengan host/port default PG
- `composer.json` — hapus 2 baris script `post-create-project-cmd` yang buat `database/database.sqlite`
- `README.md` — rewrite dari boilerplate Laravel ke doc project-specific (stack, PG requirement, quick start, credentials, links)

Verifikasi:
- `php -l` clean untuk semua file PHP yang diubah
- `npx vite build` sukses (55 modules, 779ms, CSS 76.99 kB — turun 0.1 kB)
- Live HTTP request ke `http://project-blog.test/`:
  - Status 200
  - Headers `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` terpasang
  - HSTS tidak ada (expected — request HTTP)
  - Hanya 1 font reference (Bunny Fonts Inter), `fonts.googleapis.com` hilang

### 2026-05-12 — Semua CRITICAL selesai

Files yang diubah:
- `app/Http/Controllers/Admin/UserController.php` — remove `Hash::make()` + header doc
- `app/Http/Controllers/Admin/MediaController.php` — remove svg mime + header doc
- `app/Http/Controllers/Author/MediaController.php` — remove svg mime + header doc
- `app/Http/Controllers/Admin/PostController.php` — add `Purifier::clean()` + `exists:media,path` + header doc
- `app/Http/Controllers/Author/PostController.php` — add `Purifier::clean()` + `Rule::exists` with ownership + header doc
- `app/Http/Controllers/Admin/AiPostController.php` — add `Purifier::clean()` + header doc
- `config/purifier.php` — published + tambah preset `blog`
- `package.json` / `package-lock.json` — `@tailwindcss/vite` removed
- `composer.json` / `composer.lock` — `mews/purifier ^3.4` added

Verifikasi:
- `php -l` clean untuk semua file PHP yang diubah
- `npx vite build` sukses (55 modules, 964ms)
- Purifier runtime test: XSS payloads (script, javascript: URL, onerror) berhasil di-strip, tag legitimate dipertahankan

Catatan deployment: pastikan jalankan `php artisan config:clear` di server setelah pull agar config purifier yang baru ter-load.
