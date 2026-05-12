# Analisis Lengkap & Rekomendasi Optimisasi — Project Blog Laravel

**Tanggal awal:** 2026-04-13
**Direvisi:** 2026-05-12 (verifikasi ulang terhadap kode aktual)
**Project:** ModernBlog / AndBlog (Laravel 13 + Vite + Tailwind + Alpine.js + PostgreSQL)

---

## Legenda Status Revisi

- ✅ **VALID** — Masalah masih ada, rekomendasi masih tepat
- ⚠️ **PARTIAL** — Masalah masih ada tetapi ada nuansa / rekomendasi perlu disesuaikan
- ❌ **OBSOLETE** — Sudah diperbaiki atau tidak lagi relevan dengan kondisi kode saat ini

---

## CRITICAL — Harus Segera Diperbaiki

### 1. Bug: Password Double-Hashing — ✅ VALID

**File:** `app/Http/Controllers/Admin/UserController.php` (line 41, 73)

```php
$validated['password'] = Hash::make($validated['password']);
```

Model `User` punya cast `'password' => 'hashed'` yang otomatis hash. Password di-hash **2 kali** → user yang dibuat via admin panel **tidak bisa login**.

**Status 2026-05-12:** Bug masih ada di line 41 (method `store`) dan line 73 (method `update`).

**Solusi:** Hapus `Hash::make()` di controller, biarkan cast yang handle.

```php
// Sebelum (BUG)
$validated['password'] = Hash::make($validated['password']);

// Sesudah (FIX)
// Tidak perlu hash manual, cast 'hashed' di model sudah otomatis
```

---

### 2. XSS Vulnerability: Unescaped HTML — ✅ VALID

**File:** `resources/views/posts/show.blade.php` (line 92)

```php
{!! $post->body !!}
```

Body post (termasuk output dari AI Gemini) di-render tanpa sanitasi. Author bisa inject `<script>` → **Stored XSS**.

**Status 2026-05-12:** Masih raw HTML tanpa sanitasi.

**Solusi:** Install `mews/purifier` dan sanitasi HTML sebelum disimpan.

```bash
composer require mews/purifier
```

```php
// Di controller store/update post:
$validated['body'] = clean($validated['body']);
```

---

### 3. SVG Upload = Stored XSS — ✅ VALID

**File:** `app/Http/Controllers/Admin/MediaController.php` (line 34) & `app/Http/Controllers/Author/MediaController.php` (line 30)

SVG diizinkan di upload (`mimes:...,svg`), padahal SVG bisa mengandung `<script>`.

**Status 2026-05-12:** Kedua controller masih mengizinkan SVG.

**Solusi:** Hapus `svg` dari allowed mimes.

```php
// Sebelum
'files.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',

// Sesudah
'files.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
```

---

### 4. `featured_image_path` Tidak Divalidasi — ✅ VALID

**File:** `app/Http/Controllers/Admin/PostController.php` (line 60) & `app/Http/Controllers/Author/PostController.php` (line 60)

```php
$validated['featured_image'] = $request->featured_image_path; // langsung dari request!
```

Bisa dieksploitasi untuk path traversal / overwrite field dengan path sembarang.

**Status 2026-05-12:** Masih ada di kedua controller (store + update).

**Solusi:** Validasi bahwa path berada dalam direktori yang diizinkan.

```php
if ($request->filled('featured_image_path')) {
    $path = $request->featured_image_path;
    if (preg_match('#^(media|posts)/#', $path) && !str_contains($path, '..')) {
        $validated['featured_image'] = $path;
    }
}
```

Alternatif lebih kuat: pastikan `$path` mengacu ke record `Media` yang benar-benar ada di DB milik user.

---

### 5. Konflik Tailwind v3 vs v4 — ✅ VALID (perlu koreksi ringan)

**File:** `package.json`

`tailwindcss: ^3.1.0` + `@tailwindcss/vite: ^4.0.0`.

**Status 2026-05-12:** Masih terdaftar keduanya. **Nuansa:** `vite.config.js` **tidak** memakai plugin `@tailwindcss/vite`, dan `tailwind.config.js` masih gaya v3. Jadi `@tailwindcss/vite` adalah dead dependency — aman dihapus tanpa efek build.

**Solusi:** Hapus `@tailwindcss/vite` dari devDependencies.

```bash
npm uninstall @tailwindcss/vite
```

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

### 8. N+1 Query pada Comment Replies — ✅ VALID

**File:** `app/Http/Controllers/PostController.php` (line 47)

```php
$post->load(['user', 'category', 'tags', 'approvedComments.replies', 'approvedComments.user']);
// TIDAK: 'approvedComments.replies.user' ← N+1
```

View `posts/show.blade.php` (line 186) mengakses `$reply->user->avatar_url` per iterasi → trigger 1 query per reply.

**Status 2026-05-12:** N+1 aktif dan terverifikasi.

**Solusi:**

```php
$post->load([
    'user', 'category', 'tags',
    'approvedComments.user',
    'approvedComments.replies.user',
]);
```

---

### 9. Admin Dashboard: 6 Query Terpisah — ✅ VALID

**File:** `app/Http/Controllers/Admin/DashboardController.php` (lines 14-21)

Masih 6 query agregasi terpisah.

**Status 2026-05-12:** Valid. **Catatan positif:** `Author/DashboardController` justru **sudah** memakai pola `selectRaw` conditional aggregation. Tinggal menyamakan pola ke sisi admin.

**Solusi (PostgreSQL-ready):**

```php
$postStats = Post::selectRaw("
    COUNT(*) AS total_posts,
    COUNT(*) FILTER (WHERE status = 'published') AS published_posts,
    COALESCE(SUM(views_count), 0) AS total_views
")->first();

$commentStats = Comment::selectRaw("
    COUNT(*) AS total_comments,
    COUNT(*) FILTER (WHERE is_approved = false) AS pending_comments
")->first();

$stats = [
    'total_posts'      => $postStats->total_posts,
    'published_posts'  => $postStats->published_posts,
    'total_views'      => $postStats->total_views,
    'total_comments'   => $commentStats->total_comments,
    'pending_comments' => $commentStats->pending_comments,
    'total_users'      => User::count(),
];
```

Catatan: `COUNT(*) FILTER (WHERE ...)` adalah sintaks PostgreSQL yang lebih ringkas dari `SUM(CASE WHEN ...)`. Pakai `SUM(CASE ...)` jika butuh kompatibel cross-DB.

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

### 15. Tidak Ada Security Headers — ✅ VALID

**Status 2026-05-12:** `bootstrap/app.php` hanya append `SetLocale`. Tidak ada middleware yang set security headers.

**Solusi:** Buat middleware `SecurityHeaders`.

```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        // CSP dipikirkan terpisah — nontrivial karena ada inline Alpine events + Google Fonts

        return $response;
    }
}
```

Daftarkan di `bootstrap/app.php`:

```php
$middleware->web(append: [
    SetLocale::class,
    SecurityHeaders::class,
]);
```

Catatan: `X-XSS-Protection` sudah deprecated (tidak perlu ditambah). CSP butuh riset terpisah karena view pakai inline handler Alpine.js.

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

### 17. Migration PostgreSQL-Specific — ⚠️ PARTIAL (solusi report keliru)

**File:** `database/migrations/2024_01_02_000001_update_posts_add_pending_rejected_status.php`

**Status 2026-05-12:** `.env` aktif: `DB_CONNECTION=pgsql`. Project **ditarget ke PostgreSQL** sebagai DB utama. Migration ini memang PostgreSQL-specific (`ALTER TABLE ... CHECK CONSTRAINT` dengan cast `::text`).

**Nuansa:** Solusi di report awal (drop column + recreate enum) **berbahaya**:
- `dropColumn('status')` akan menghilangkan data status existing
- Rollback/forward akan kehilangan informasi `pending` dan `rejected`
- Enum `draft|pending|published|rejected` tidak didukung seragam di semua DB

**Rekomendasi revisi:**
1. **Dokumentasikan** di `README.md` bahwa PostgreSQL adalah requirement wajib.
2. Update `.env.example` dari `DB_CONNECTION=sqlite` → `DB_CONNECTION=pgsql` agar sesuai kenyataan project.
3. Hapus script `post-create-project-cmd` di `composer.json` yang membuat `database/database.sqlite` (baris 64).
4. Untuk fleksibilitas cross-DB di masa depan: gunakan kolom `VARCHAR` + validasi aplikasi `in:draft,pending,published,rejected`, bukan CHECK constraint DB.

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

### 19. Newsletter Form Non-Fungsional — ✅ VALID

**File:** `resources/views/home.blade.php` (line 163-178)

**Status 2026-05-12:** Masih `<input>` + `<button>` **tanpa** `<form>` wrapper, tanpa action, tanpa handler backend. Menyesatkan user.

**Solusi:** Hapus section atau implementasikan (butuh tabel `newsletter_subscribers`, controller, validasi email, double-opt-in kalau serius).

---

### 20. Duplicate Font Loading — ✅ VALID (dengan nuansa)

**Status 2026-05-12 (terverifikasi):**
- `resources/css/app.css` line 1 → Google Fonts **Inter**
- `resources/views/layouts/blog.blade.php` line 21 → Google Fonts **Inter** (duplikat!)
- `resources/views/layouts/app.blade.php` line 12 → Bunny Fonts **Figtree**

Duplikasi nyata adalah **Inter di 2 tempat**. Figtree hanya dipakai layout Breeze (auth/profile) — bisa dipertahankan atau distandarkan ke Inter.

**Solusi:** Hapus `@import` Inter dari `app.css`, load hanya di layout dengan preconnect.

```html
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
```

Kenapa Bunny? Sama API dengan Google Fonts tapi **tanpa tracking** dan bisa dipakai di EU tanpa masalah GDPR.

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

### 22. Zero Business Logic Tests — ✅ VALID

**Status 2026-05-12:** `tests/Feature` hanya berisi test bawaan Breeze (Auth, ProfileTest, ExampleTest). Tidak ada test untuk: Post CRUD (Admin/Author), Comment moderation, Media upload, Policy, GeminiService, trait `GeneratesUniqueSlug`.

**Solusi:** Tambahkan suite test minimal + factories yang belum ada:

```
tests/
├── Feature/
│   ├── Admin/
│   │   ├── PostControllerTest.php
│   │   ├── CategoryControllerTest.php
│   │   ├── CommentControllerTest.php
│   │   ├── MediaControllerTest.php
│   │   └── UserControllerTest.php       # sekalian tangkap regression bug #1
│   ├── Author/
│   │   └── PostControllerTest.php
│   ├── PostControllerTest.php           # Public blog
│   └── CommentControllerTest.php
├── Unit/
│   ├── Models/
│   │   ├── PostTest.php                 # Scopes, accessors
│   │   └── UserTest.php                 # isAdmin/isAuthor
│   ├── Traits/
│   │   └── GeneratesUniqueSlugTest.php
│   └── Policies/
│       └── PostPolicyTest.php

database/factories/
├── PostFactory.php
├── CategoryFactory.php
├── TagFactory.php
├── CommentFactory.php
└── MediaFactory.php
```

Catatan: test DB perlu pakai SQLite in-memory atau PostgreSQL test DB. Karena migration `2024_01_02_000001` pakai raw PG SQL, **SQLite tidak bisa**. Solusi: test DB pakai PostgreSQL terpisah.

---

### 23. Tidak Ada Docker/CI-CD — ✅ VALID

**Status 2026-05-12:** Tidak ada `.github/`, `Dockerfile`, atau `docker-compose.yml`.

**Solusi minimal GitHub Actions (perlu adjustment karena PG-only):**

```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_PASSWORD: postgres
          POSTGRES_DB: blog_test
        ports: ['5432:5432']
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pgsql, pdo_pgsql
      - run: composer install --no-interaction --prefer-dist
      - run: cp .env.example .env && php artisan key:generate
      - run: php artisan migrate --force
        env:
          DB_CONNECTION: pgsql
          DB_HOST: 127.0.0.1
          DB_PORT: 5432
          DB_DATABASE: blog_test
          DB_USERNAME: postgres
          DB_PASSWORD: postgres
      - run: php artisan test
```

---

## Ringkasan Revisi — Status Semua Item

| #  | Item                                              | Status 2026-05-12 |
| -- | ------------------------------------------------- | ----------------- |
| 1  | Password double-hashing                            | ✅ VALID           |
| 2  | XSS HTML post body                                 | ✅ VALID           |
| 3  | SVG upload                                         | ✅ VALID           |
| 4  | `featured_image_path` tidak divalidasi             | ✅ VALID           |
| 5  | Konflik Tailwind v3/v4                             | ✅ VALID           |
| 6  | Duplikasi Admin vs Author controller               | ✅ VALID           |
| 7  | Validasi post duplikasi 5x                         | ✅ VALID           |
| 8  | N+1 comment replies                                | ✅ VALID           |
| 9  | Admin dashboard 6 query                            | ✅ VALID           |
| 10 | `Setting::get()` tanpa cache                       | ❌ OBSOLETE        |
| 11 | Slug uniqueness loop                               | ✅ VALID (low impact) |
| 12 | Homepage & sitemap tanpa cache                     | ✅ VALID           |
| 13 | Cache & session driver = database                  | ⚠️ PARTIAL (deployment) |
| 14 | Rate limiting login                                | ⚠️ PARTIAL (sudah ada di LoginRequest) |
| 15 | Security headers                                   | ✅ VALID           |
| 16 | `APP_DEBUG=true`                                   | ⚠️ PARTIAL (local OK) |
| 17 | Migration PostgreSQL-specific                      | ⚠️ PARTIAL (solusi lama keliru) |
| 18 | Missing indexes                                    | ⚠️ PARTIAL (4 dari 8 sudah ada) |
| 19 | Newsletter form non-fungsional                     | ✅ VALID           |
| 20 | Duplicate font loading                             | ✅ VALID           |
| 21 | Manual `.prose` styles                             | ✅ VALID           |
| 22 | Zero business logic tests                          | ✅ VALID           |
| 23 | Tidak ada Docker/CI-CD                             | ✅ VALID           |

**Ringkasan angka:** 16 VALID penuh, 6 PARTIAL, 1 OBSOLETE.

---

## Ringkasan Action Items (Urut Prioritas Eksekusi)

| #  | Action                                                   | Impact               | Effort    |
| -- | -------------------------------------------------------- | -------------------- | --------- |
| 1  | Fix double-hashing bug di `UserController`               | CRITICAL Bug         | 5 menit   |
| 2  | Sanitasi HTML — install `mews/purifier`                  | CRITICAL Security    | 30 menit  |
| 3  | Hapus SVG dari allowed upload types                      | CRITICAL Security    | 5 menit   |
| 4  | Validasi `featured_image_path`                           | CRITICAL Security    | 15 menit  |
| 5  | Hapus `@tailwindcss/vite` dari `package.json`            | CRITICAL Build       | 5 menit   |
| 6  | Hapus / implementasikan newsletter form                  | LOW UX               | 5 menit   |
| 7  | Fix N+1 queries — eager load `replies.user`              | MEDIUM Performance   | 5 menit   |
| 8  | Konsolidasi query Admin Dashboard                        | MEDIUM Performance   | 15 menit  |
| 9  | Tambah index yang masih missing                          | MEDIUM Performance   | 15 menit  |
| 10 | Tambah security headers middleware                       | MEDIUM Security      | 20 menit  |
| 11 | `Cache::remember` untuk homepage & sitemap               | MEDIUM Performance   | 45 menit  |
| 12 | Ekstrak Form Requests & Services                         | HIGH Maintainability | 2-3 jam   |
| 13 | Deduplikasi font loading (Inter 2x)                      | LOW Performance      | 10 menit  |
| 14 | Ganti manual `.prose` dengan `@tailwindcss/typography`   | LOW Quality          | 30 menit  |
| 15 | Update `.env.example` ke `DB_CONNECTION=pgsql` + README  | LOW Documentation    | 10 menit  |
| 16 | Tulis feature tests untuk core business logic            | MEDIUM Quality       | 4-6 jam   |
| 17 | Setup GitHub Actions CI dengan PG service                | MEDIUM Quality       | 30 menit  |

**Quick-win ≤30 menit (tangkap ~80% value):** #1, #2, #3, #4, #5, #6, #7, #8, #10, #13, #15
