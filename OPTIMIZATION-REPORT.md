# Analisis Lengkap & Rekomendasi Optimisasi — Project Blog Laravel

**Tanggal:** 2026-04-13
**Project:** ModernBlog (Laravel 13 + Vite + Tailwind + Alpine.js)

---

## CRITICAL — Harus Segera Diperbaiki

### 1. Bug: Password Double-Hashing

**File:** `app/Http/Controllers/Admin/UserController.php` (line 42, 73)

```php
$validated['password'] = Hash::make($validated['password']);
```

Model `User` sudah punya cast `'password' => 'hashed'` yang otomatis hash. Jadi password di-hash **2 kali** → user yang dibuat via admin panel **tidak bisa login**.

**Solusi:** Hapus `Hash::make()` di controller, biarkan cast yang handle.

```php
// Sebelum (BUG)
$validated['password'] = Hash::make($validated['password']);

// Sesudah (FIX)
// Tidak perlu hash manual, cast 'hashed' di model sudah otomatis
```

---

### 2. XSS Vulnerability: Unescaped HTML

**File:** `resources/views/posts/show.blade.php` (line 92)

```php
{!! $post->body !!}
```

Body post (termasuk dari AI Gemini) di-render tanpa sanitasi. Author bisa inject `<script>` tag → **Stored XSS**.

**Solusi:** Install `mews/purifier` dan sanitasi HTML sebelum disimpan ke database.

```bash
composer require mews/purifier
```

```php
// Di controller store/update:
$validated['body'] = clean($validated['body']); // HTMLPurifier
```

---

### 3. SVG Upload = Stored XSS

**File:** `app/Http/Controllers/Admin/MediaController.php` & `app/Http/Controllers/Author/MediaController.php`

SVG diizinkan di upload (`mimes:...svg`), padahal SVG bisa mengandung JavaScript.

**Solusi:** Hapus `svg` dari allowed mimes.

```php
// Sebelum
'files.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',

// Sesudah
'files.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
```

---

### 4. `featured_image_path` Tidak Divalidasi

**File:** `app/Http/Controllers/Admin/PostController.php` & `app/Http/Controllers/Author/PostController.php`

```php
$validated['featured_image'] = $request->featured_image_path; // langsung dari request!
```

Bisa dieksploitasi untuk path traversal.

**Solusi:** Validasi bahwa path berada dalam direktori yang diizinkan.

```php
if ($request->filled('featured_image_path')) {
    $path = $request->featured_image_path;
    // Pastikan path dimulai dengan 'media/' atau 'posts/' dan tidak mengandung '..'
    if (preg_match('/^(media|posts)\/[^\.]{2}/', $path) && !str_contains($path, '..')) {
        $validated['featured_image'] = $path;
    }
}
```

---

### 5. Konflik Tailwind v3 vs v4

**File:** `package.json`

`tailwindcss: ^3.1.0` + `@tailwindcss/vite: ^4.0.0` → **incompatible**.

**Solusi:** Hapus `@tailwindcss/vite` dari devDependencies.

```bash
npm uninstall @tailwindcss/vite
```

---

## HIGH — Duplikasi Kode Masif

### 6. Admin vs Author Controllers ~90% Identik

| Admin                        | Author                        | Duplikasi |
| ---------------------------- | ----------------------------- | --------- |
| `Admin/PostController.php`   | `Author/PostController.php`   | ~80%      |
| `Admin/MediaController.php`  | `Author/MediaController.php`  | ~90%      |
| `Admin/CommentController.php`| `Author/CommentController.php`| ~60%      |

**Solusi:** Ekstrak ke **Form Requests** (validasi), **Service classes** (business logic), dan **Traits** (shared behavior). Bisa menghilangkan ~500 baris kode duplikat.

Contoh struktur:

```
app/
├── Http/
│   └── Requests/
│       ├── StorePostRequest.php      # Shared validation rules
│       └── UpdatePostRequest.php
├── Services/
│   ├── PostService.php               # Shared store/update/delete logic
│   ├── MediaService.php              # Shared upload/delete logic
│   └── GeminiService.php             # (sudah ada)
```

---

### 7. Validasi Post Diduplikasi 5 Kali

Rules validasi post di-copy-paste di:
- `Admin/PostController::store`
- `Admin/PostController::update`
- `Author/PostController::store`
- `Author/PostController::update`
- `AiPostController::store`

**Solusi:** Buat `StorePostRequest` dan `UpdatePostRequest` Form Request classes.

```php
// app/Http/Requests/StorePostRequest.php
class StorePostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'body' => 'required|max:1000000',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'excerpt' => 'nullable|max:500',
            'status' => 'required|in:draft,pending,published',
        ];
    }
}
```

---

## MEDIUM — Performance & Query Optimization

### 8. N+1 Query pada Comment Replies

**File:** `app/Http/Controllers/PostController.php`

```php
// Saat ini:
'approvedComments.replies', 'approvedComments.user'
// TAPI TIDAK: 'approvedComments.replies.user' ← N+1!
```

Setiap reply memicu query terpisah untuk load user-nya.

**Solusi:**

```php
$post->load([
    'approvedComments.user',
    'approvedComments.replies.user', // Tambahkan ini
]);
```

---

### 9. Admin Dashboard: 6 Query Terpisah

**File:** `app/Http/Controllers/Admin/DashboardController.php` (lines 14-21)

```php
$stats = [
    'total_posts' => Post::count(),           // query 1
    'published_posts' => Post::where(...),     // query 2
    'total_comments' => Comment::count(),      // query 3
    'pending_comments' => Comment::where(...), // query 4
    'total_views' => Post::sum(...),           // query 5
    'total_users' => User::count(),            // query 6
];
```

**Solusi:** Gabung jadi 2 query dengan conditional aggregation.

```php
$postStats = Post::selectRaw("
    COUNT(*) as total_posts,
    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_posts,
    SUM(views_count) as total_views
")->first();

$commentStats = Comment::selectRaw("
    COUNT(*) as total_comments,
    SUM(CASE WHEN is_approved = false THEN 1 ELSE 0 END) as pending_comments
")->first();

$stats = [
    'total_posts' => $postStats->total_posts,
    'published_posts' => $postStats->published_posts,
    'total_views' => $postStats->total_views,
    'total_comments' => $commentStats->total_comments,
    'pending_comments' => $commentStats->pending_comments,
    'total_users' => User::count(),
];
```

---

### 10. `Setting::get()` Tanpa Cache

**File:** `app/Models/Setting.php`

Setiap panggilan `Setting::get('key')` = 1 query DB. Dipanggil berkali-kali per request di layout/views.

**Solusi:**

```php
public static function get(string $key, $default = null): ?string
{
    return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    });
}

// Jangan lupa clear cache saat setting diupdate:
public static function set(string $key, $value): void
{
    static::updateOrCreate(['key' => $key], ['value' => $value]);
    Cache::forget("setting.{$key}");
}
```

---

### 11. Slug Uniqueness: Loop Query

**File:** `app/Traits/GeneratesUniqueSlug.php`

Melakukan query per iterasi. Jika ada 100 slug serupa → 100 query.

**Solusi:** Satu query lalu hitung counter di PHP.

```php
public static function generateUniqueSlug(string $title, string $modelClass, ?int $excludeId = null): string
{
    $baseSlug = Str::slug($title);

    $existingSlugs = $modelClass::where('slug', 'LIKE', $baseSlug . '%')
        ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
        ->pluck('slug')
        ->toArray();

    if (!in_array($baseSlug, $existingSlugs)) {
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

### 12. Homepage & Sitemap Tanpa Cache

**File:** `app/Http/Controllers/HomeController.php`, `SitemapController.php`

Hit DB setiap request tanpa `Cache::remember()`.

**Solusi:**

```php
// HomeController
public function index()
{
    $featuredPosts = Cache::remember('home.featured', 3600, function () {
        return Post::published()->featured()->with(['category', 'user'])->latest('published_at')->take(5)->get();
    });

    $latestPosts = Cache::remember('home.latest', 1800, function () {
        return Post::published()->with(['category', 'user'])->latest('published_at')->take(9)->get();
    });

    // ...
}

// SitemapController
public function index()
{
    $data = Cache::remember('sitemap', 3600, function () {
        return [
            'posts' => Post::published()->select('slug', 'updated_at')->get(),
            'categories' => Category::select('slug', 'updated_at')->get(),
            'tags' => Tag::select('slug', 'updated_at')->get(),
        ];
    });

    // ...
}
```

---

### 13. Cache & Session Driver = `database`

**File:** `.env`

Untuk production, ganti ke **Redis** untuk performa jauh lebih baik.

```env
CACHE_STORE=redis
SESSION_DRIVER=redis
```

---

## MEDIUM — Security & Config

### 14. Tidak Ada Rate Limiting di Login

`POST /login` tidak punya `throttle` middleware → rentan brute force.

**Solusi:** Tambahkan di `routes/auth.php`:

```php
Route::post('login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('throttle:5,1'); // 5 attempts per minute
```

---

### 15. Tidak Ada Security Headers

Missing: `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Strict-Transport-Security`.

**Solusi:** Buat middleware `SecurityHeaders`:

```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        return $response;
    }
}
```

Daftarkan di `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(SecurityHeaders::class);
})
```

---

### 16. `APP_DEBUG=true` di `.env`

Harus `false` di production — expose stack trace & environment variables.

```env
APP_DEBUG=false
```

---

### 17. Migration PostgreSQL-Specific

**File:** `database/migrations/2024_01_02_000001_update_posts_add_pending_rejected_status.php`

Pakai raw SQL PostgreSQL → **gagal di SQLite/MySQL**.

**Solusi:** Gunakan pendekatan database-agnostic:

```php
public function up(): void
{
    Schema::table('posts', function (Blueprint $table) {
        $table->dropColumn('status');
    });

    Schema::table('posts', function (Blueprint $table) {
        $table->enum('status', ['draft', 'pending', 'published', 'rejected'])->default('draft');
    });
}
```

---

### 18. Missing Database Indexes

Tambahkan index untuk kolom yang sering di-query:

```php
// Migration baru
Schema::table('comments', function (Blueprint $table) {
    $table->index('parent_id');
});

Schema::table('posts', function (Blueprint $table) {
    $table->index('is_featured');
    $table->index('category_id');
});

Schema::table('media', function (Blueprint $table) {
    $table->index('user_id');
});
```

---

## LOW — Code Quality

### 19. Newsletter Form Non-Fungsional

**File:** `resources/views/home.blade.php` (lines 170-175)

Form newsletter tanpa `<form>` tag, tanpa action, tanpa backend handler. Menyesatkan user.

**Solusi:** Hapus section newsletter atau implementasikan dengan benar.

---

### 20. Duplicate Font Loading

- `app.css` → Google Fonts (Inter)
- `layouts/blog.blade.php` → Google Fonts (Inter) lagi
- `layouts/app.blade.php` → Bunny Fonts (Figtree)

**Solusi:** Pilih satu font dan satu sumber. Load hanya di satu tempat (layout utama) dengan `font-display: swap`.

```html
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
```

---

### 21. Manual `.prose` Styles (60+ baris)

**File:** `resources/css/app.css`

Bisa diganti dengan plugin `@tailwindcss/typography`.

```bash
npm install @tailwindcss/typography
```

```js
// tailwind.config.js
plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'), // Tambahkan ini
],
```

Lalu ganti class manual `.prose` dengan class bawaan plugin.

---

### 22. Zero Business Logic Tests

Hanya ada test bawaan Breeze. Tidak ada test untuk Post CRUD, comments, media, AI generation, policies.

**Solusi:** Tambahkan minimal test berikut:

```
tests/
├── Feature/
│   ├── Admin/
│   │   ├── PostControllerTest.php
│   │   ├── CategoryControllerTest.php
│   │   ├── CommentControllerTest.php
│   │   └── MediaControllerTest.php
│   ├── Author/
│   │   └── PostControllerTest.php
│   ├── PostControllerTest.php          # Public blog
│   └── CommentControllerTest.php       # Public comments
├── Unit/
│   ├── Models/
│   │   ├── PostTest.php                # Scopes, accessors
│   │   └── SettingTest.php
│   ├── Traits/
│   │   └── GeneratesUniqueSlugTest.php
│   └── Policies/
│       └── PostPolicyTest.php
```

Juga buat factories yang belum ada:

```
database/factories/
├── PostFactory.php
├── CategoryFactory.php
├── TagFactory.php
├── CommentFactory.php
└── MediaFactory.php
```

---

### 23. Tidak Ada Docker/CI-CD

Tidak ada `Dockerfile`, `docker-compose.yml`, atau GitHub Actions workflow.

**Solusi:** Tambahkan minimal:

```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - run: composer install --no-interaction
      - run: cp .env.example .env && php artisan key:generate
      - run: php artisan test
```

---

## Ringkasan Action Items (Urut Prioritas)

| #  | Action                                                  | Impact          | Effort   |
| -- | ------------------------------------------------------- | --------------- | -------- |
| 1  | Fix double-hashing bug di `UserController`              | CRITICAL Bug    | 5 menit  |
| 2  | Sanitasi HTML — install `mews/purifier`                 | CRITICAL Security | 30 menit |
| 3  | Hapus SVG dari allowed upload types                     | CRITICAL Security | 5 menit  |
| 4  | Validasi `featured_image_path`                          | CRITICAL Security | 15 menit |
| 5  | Fix Tailwind v3/v4 conflict — hapus `@tailwindcss/vite` | CRITICAL Build  | 5 menit  |
| 6  | Ekstrak Form Requests & Services — hilangkan duplikasi  | HIGH Maintainability | 2-3 jam  |
| 7  | Tambah `Cache::remember()` di homepage, sidebar, sitemap | MEDIUM Performance | 1 jam    |
| 8  | Fix N+1 queries — eager load `replies.user`             | MEDIUM Performance | 15 menit |
| 9  | Tambah security headers & login throttle                | MEDIUM Security | 30 menit |
| 10 | Tulis feature tests untuk core business logic           | MEDIUM Quality  | 4-6 jam  |
