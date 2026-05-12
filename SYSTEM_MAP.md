# SYSTEM MAP - project-blog

Peta navigasi proyek blog Laravel dengan fitur multi-role (admin/author/reader), manajemen konten (posts, categories, tags, comments), media library, dan AI post generator via Google Gemini.

---

# Project Summary

## Tujuan Aplikasi
Platform blog modern (disebut "ModernBlog" di seeder) dengan:
- Artikel berlabel status workflow (draft → pending → published/rejected)
- Sistem komentar bersarang (nested) dengan moderasi
- Multi-role: `admin`, `author`, `reader`
- Media library terpusat dengan picker modal
- AI-generated posts via **Google Gemini API**
- SEO: sitemap.xml, robots.txt, meta tags
- Bilingual (ID / EN) via session switcher

## Tech Stack Utama
- **PHP**: `^8.3`
- **Laravel**: `^13.0`
- **Database**: Konfigurasi `pgsql` tersedia; default `.env.example` menggunakan `sqlite`. Migration `2024_01_02_000001_update_posts_add_pending_rejected_status.php` memakai **raw SQL PostgreSQL** (`ALTER TABLE ... CHECK CONSTRAINT`). Artinya proyek **ditargetkan untuk PostgreSQL di production**.
- **Auth**: Laravel Breeze `^2.4` (Blade stack)
- **Frontend**: Blade + **Alpine.js 3** + **Tailwind CSS 3** (via `laravel-vite-plugin`)
- **Queue**: `database` driver (tabel `jobs`)
- **Cache**: `database` driver (tabel `cache`)
- **Session**: `database` driver (tabel `sessions`)
- **Mail**: `log` driver default (tidak aktif)
- **Filesystem**: disk `public` untuk upload (posts, avatars, media)
- **HTTP Client**: `Illuminate\Support\Facades\Http` (Gemini API)
- **Dev tools**: Pail (log viewer), Pint, PHPUnit 12, Collision, Mockery

## Paket Composer Penting
- `laravel/framework ^13.0`
- `laravel/breeze ^2.4` (dev)
- `laravel/tinker ^3.0`
- `laravel/pail ^1.2` (dev)
- `mews/purifier ^3.4` — HTMLPurifier wrapper, dipakai untuk sanitasi HTML body post (anti Stored XSS)
- `fakerphp/faker` (dev)
- **Tidak ada**: Livewire, Inertia, Filament, Sanctum, Spatie Permission, Horizon. Akses role diimplementasi manual via kolom `users.role` + middleware.

## Pola Arsitektur
- **MVC standar Laravel** (Controller → Model → View Blade)
- **Service layer minimal**: hanya `App\Services\GeminiService` untuk integrasi eksternal
- **Trait sederhana**: `App\Traits\GeneratesUniqueSlug` dipakai oleh PostController (Admin, Author, AI)
- **Policy**: hanya `PostPolicy`
- **Route prefix grouping**: public, `author.`, `admin.`, auth
- **Tidak ada**: Jobs, Events, Listeners, Commands (hanya `inspire` bawaan), Observers, Notifications, Mail, Livewire, Filament, API routes

---

# Core Logic Flow (Function-Level Flowchart)

## 1. Public - Menampilkan Homepage
```
GET /
  -> web middleware stack + SetLocale[handle]
  -> HomeController@index
  -> Post::published()->featured()->with(user,category)->take(3)
  -> Post::published()->with(user,category,tags)->take(6)
  -> Category::withCount(publishedPosts)->has(publishedPosts)->take(8)
  -> view('home')
```

## 2. Public - Detail Artikel + Increment View
```
GET /blog/{post:slug}
  -> PostController@show (Post model route-binding via slug)
  -> abort(404) jika status != 'published'
  -> session()->has('viewed_post_ID') ? skip : $post->increment('views_count') + session put
  -> load(user, category, tags, approvedComments.replies, approvedComments.user)
  -> Post::published()->where(category_id)->take(3)  [related]
  -> view('posts.show')
```

## 3. Public - Submit Komentar (guest/auth)
```
POST /blog/{post}/comments
  -> throttle:6,1 middleware
  -> CommentController@store
  -> Validate: body 3..1000, parent_id exists; if guest: author_name+email
  -> post->comments()->create(...): is_approved = auth()->check()
  -> redirect back with success
```

## 4. Auth - Login + Role-aware Redirect
```
POST /login
  -> guest middleware
  -> AuthenticatedSessionController@store(LoginRequest)
  -> LoginRequest::authenticate -> RateLimiter (5 attempts, keyed by email|ip)
  -> Auth::attempt + session regenerate
  -> if isAdmin -> redirect admin.dashboard ; else redirect home
```

## 5. Author - Create Post + Optional Submit Review
```
POST /author/posts
  -> auth + author middleware
  -> Author\PostController@store
  -> Validate: title, category_id, excerpt, body, featured_image(image|max:2048), featured_image_path(exists:media,path WHERE user_id=auth), tags[]
  -> Purifier::clean(body, 'blog')  [sanitize HTML, preset 'blog']
  -> GeneratesUniqueSlug::generateUniqueSlug(title, Post::class)
  -> featured_image: featured_image_path (dari media picker, validated milik user) OR upload ke disk 'public/posts'
  -> status = 'pending' (jika submit_review) | 'draft'
  -> Post::create + $post->tags()->sync($tagIds)
  -> redirect author.posts.index
```

## 6. Admin - Approve / Reject Post
```
PATCH /admin/posts/{post}/approve
  -> auth + admin middleware
  -> Admin\PostController@approve
  -> $post->update(status=published, published_at=now(), rejection_reason=null)

PATCH /admin/posts/{post}/reject
  -> Validate rejection_reason required|max:1000
  -> $post->update(status=rejected, rejection_reason=...)
```

## 7. Admin - AI Post Generator (External API Flow)
```
POST /admin/ai-posts/generate
  -> auth + admin middleware
  -> Admin\AiPostController@generate(GeminiService)
  -> Validate: topic max:500, language in:id,en
  -> GeminiService::generatePost(topic, language)
      -> Http::withHeaders(x-goog-api-key)->timeout(60)
         ->post(https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent)
      -> parse JSON (strip ```json fences), require title+excerpt+body
      -> throws RuntimeException on failure
  -> view('admin.posts.ai-generate', [generated, categories, tags])

POST /admin/ai-posts
  -> Admin\AiPostController@store
  -> Validate + Purifier::clean(body, 'blog') + generateUniqueSlug -> Post::create + tags sync
```

## 8. Media Upload (Admin/Author)
```
POST /author/media  (atau /admin/media)
  -> auth + author|admin middleware
  -> MediaController@store
  -> Validate: files[] max:10, each image|max:5120 KB, mimes jpeg,png,jpg,gif,webp (SVG tidak diizinkan — anti XSS)
  -> foreach file: Str::uuid().ext -> storeAs('media', $filename, 'public')
  -> getimagesize untuk width/height
  -> Media::create(user_id, filename, path, mime_type, size, width, height)
  -> wantsJson ? return JSON : redirect back
```

## 9. SEO - Sitemap XML
```
GET /sitemap.xml
  -> SitemapController@index
  -> Post::published()->select(slug, updated_at, featured_image)
  -> Category + Tag lists
  -> view('sitemap')->render() + Content-Type: application/xml
```

## 10. i18n - Locale Switch
```
GET /locale/{locale}
  -> closure validates locale in [id, en]
  -> session()->put('locale', $locale)
  -> redirect back

Setiap request:
  -> SetLocale middleware (appended to web)
  -> app()->setLocale(session('locale', config('app.locale')))
```

## 11. Console
```
php artisan inspire  -> Illuminate\Foundation\Inspiring::quote()  [hanya command built-in]
```
Tidak ada scheduler (`withSchedule` tidak didefinisikan di `bootstrap/app.php`).

---

# Clean Tree

```
project-blog/
├─ app/
│  ├─ Http/
│  │  ├─ Controllers/
│  │  │  ├─ Admin/              (7: Dashboard, Post, AiPost, Category, Tag, Comment, User, Media)
│  │  │  ├─ Author/             (4: Dashboard, Post, Comment, Media)
│  │  │  ├─ Auth/                (9 Breeze controllers)
│  │  │  ├─ Controller.php       (base, pakai AuthorizesRequests trait via default)
│  │  │  ├─ HomeController.php
│  │  │  ├─ PostController.php   (public blog)
│  │  │  ├─ CommentController.php
│  │  │  ├─ ProfileController.php
│  │  │  └─ SitemapController.php
│  │  ├─ Middleware/
│  │  │  ├─ AdminMiddleware.php
│  │  │  ├─ AuthorMiddleware.php
│  │  │  └─ SetLocale.php
│  │  └─ Requests/
│  │     ├─ Auth/LoginRequest.php
│  │     └─ ProfileUpdateRequest.php
│  ├─ Models/                    (Category, Comment, Media, Post, Setting, Tag, User)
│  ├─ Policies/PostPolicy.php
│  ├─ Providers/AppServiceProvider.php
│  ├─ Services/GeminiService.php
│  ├─ Traits/GeneratesUniqueSlug.php
│  └─ View/Components/           (AppLayout.php, GuestLayout.php — Breeze)
├─ bootstrap/app.php
├─ config/                       (app, auth, cache, database, filesystems, logging, mail, purifier, queue, services, session)
├─ database/
│  ├─ factories/UserFactory.php
│  ├─ migrations/                (13 files: users, cache, jobs, categories, tags, posts+post_tag, comments, settings, media, posts-status-update, performance-indexes)
│  └─ seeders/DatabaseSeeder.php
├─ lang/
├─ public/
├─ resources/
│  ├─ css/app.css
│  ├─ js/{app.js, bootstrap.js}  (Alpine.js + axios)
│  └─ views/
│     ├─ layouts/                (app, guest, admin, author, blog, navigation)
│     ├─ components/             (Breeze UI + media-picker, seo)
│     ├─ auth/                   (login, register, forgot-password, reset-password, verify-email, confirm-password)
│     ├─ pages/                  (about, contact — via Route::view)
│     ├─ posts/                  (index, show, by-category, by-tag)
│     ├─ admin/                  (dashboard, posts, categories, tags, comments, users, media, posts/ai-generate)
│     ├─ author/                 (dashboard, posts, comments, media)
│     ├─ profile/                (edit + partials)
│     ├─ home.blade.php
│     └─ sitemap.blade.php
├─ routes/
│  ├─ web.php
│  ├─ auth.php                   (Breeze)
│  └─ console.php                (hanya 'inspire')
├─ tests/                        (Breeze default auth + profile tests)
├─ composer.json
├─ package.json
├─ tailwind.config.js
├─ vite.config.js
├─ phpunit.xml
└─ OPTIMIZATION-REPORT.md        (catatan proyek eksisting)
```

---

# Module Map (The Chapters)

## Routes
- `routes/web.php` — Semua route publik, author, admin + `require __DIR__.'/auth.php'`. **Route**
- `routes/auth.php` — Route Breeze default (register, login, password reset, verify-email, confirm-password, logout). **Route**
- `routes/console.php` — Hanya command `inspire`. **Route**

## Middleware
- `app/Http/Middleware/SetLocale.php` — `handle()`: pasang locale dari `session('locale')` jika ∈ [id, en]. Append ke grup `web`. **Middleware**
- `app/Http/Middleware/AdminMiddleware.php` — `handle()`: `abort(403)` jika non-admin. Alias `admin`. **Middleware**
- `app/Http/Middleware/AuthorMiddleware.php` — `handle()`: `abort(403)` jika non-author (admin juga author). Alias `author`. **Middleware**

## Controllers — Public
- `app/Http/Controllers/HomeController.php` — `index()`: featured + latest posts + top categories. **Controller**
- `app/Http/Controllers/PostController.php` — `index, show, byCategory, byTag`: listing + detail artikel publik dengan filter search/category/tag, increment views, related posts. **Controller**
- `app/Http/Controllers/CommentController.php` — `store(Post)`: kirim komentar (guest & auth), validasi + rate limit 6/menit. **Controller**
- `app/Http/Controllers/SitemapController.php` — `index()`: render XML sitemap. **Controller**
- `app/Http/Controllers/ProfileController.php` — `edit, update, destroy`: profil user. **Controller**

## Controllers — Author (prefix `/author`, middleware `auth,author`)
- `app/Http/Controllers/Author/DashboardController.php` — `index()`: stats post milik user + total komentar, raw `DB::raw` aggregate. **Controller**
- `app/Http/Controllers/Author/PostController.php` — `index, create, store, edit, update, destroy, submitForReview`: CRUD post milik sendiri + submit untuk review. Pakai `$this->authorize(...)` + trait `GeneratesUniqueSlug`. **Controller**
- `app/Http/Controllers/Author/MediaController.php` — `index, store, destroy, json`: media library per-user. **Controller**
- `app/Http/Controllers/Author/CommentController.php` — `index, approve, destroy`: moderasi komentar pada post milik author. **Controller**

## Controllers — Admin (prefix `/admin`, middleware `auth,admin`)
- `app/Http/Controllers/Admin/DashboardController.php` — `index()`: stats global (posts, comments, views, users). **Controller**
- `app/Http/Controllers/Admin/PostController.php` — `index, create, store, edit, update, destroy, approve, reject`: full CRUD + workflow approval. **Controller**
- `app/Http/Controllers/Admin/AiPostController.php` — `create, generate, store`: AI article generator pakai `GeminiService`. **Controller**
- `app/Http/Controllers/Admin/MediaController.php` — `index, store, destroy, json`: media library global. **Controller**
- `app/Http/Controllers/Admin/CategoryController.php` — CRUD kategori. **Controller**
- `app/Http/Controllers/Admin/TagController.php` — CRUD tag. **Controller**
- `app/Http/Controllers/Admin/CommentController.php` — `index, approve, reject, destroy`: moderasi komentar. **Controller**
- `app/Http/Controllers/Admin/UserController.php` — CRUD user + upload avatar + guard hapus diri sendiri. **Controller**

## Controllers — Auth (Breeze default, tidak dimodifikasi)
- `Auth/AuthenticatedSessionController.php` — `create, store, destroy`. Store redirect ke `admin.dashboard` jika admin, else `home`. **Controller**
- `Auth/RegisteredUserController.php` — `create, store`: registrasi (role default `reader` via migration enum default). **Controller**
- `Auth/PasswordResetLinkController.php`, `Auth/NewPasswordController.php`, `Auth/PasswordController.php`, `Auth/ConfirmablePasswordController.php`, `Auth/VerifyEmailController.php`, `Auth/EmailVerificationPromptController.php`, `Auth/EmailVerificationNotificationController.php` — Breeze standar. **Controller**

## Request Validation
- `app/Http/Requests/Auth/LoginRequest.php` — rules: `email required|email`, `password required`. `authenticate()`: rate-limit 5 attempts via `RateLimiter`, key = `lower(email)|ip`. **Request Validation**
- `app/Http/Requests/ProfileUpdateRequest.php` — rules: `name`, `email` unik (ignore self). **Request Validation**
- Validasi lain inline di controller (CommentController, PostController admin/author, CategoryController, TagController, UserController, AiPostController, MediaController).

## Services
- `app/Services/GeminiService.php` — `generatePost(topic, language='id')`: call Google Gemini `generateContent` endpoint, parse JSON response, return `['title','excerpt','body']`. Throws `RuntimeException`. **Service**
- `Mews\Purifier\Facades\Purifier` (package `mews/purifier`) — dipakai di Admin/Author PostController dan AiPostController untuk `Purifier::clean($body, 'blog')` sebelum simpan ke DB. Preset `blog` didefinisikan di `config/purifier.php`. **External Library**

## Traits
- `app/Traits/GeneratesUniqueSlug.php` — `generateUniqueSlug(title, modelClass, excludeId=null)`: loop increment hingga slug unik. Dipakai oleh Admin\PostController, Author\PostController, Admin\AiPostController. **Helper/Trait**

## Policies
- `app/Policies/PostPolicy.php` — `viewAny, view, create, update, delete`. Rule: admin selalu lolos; author hanya pemilik. **Policy**

## Models
- `app/Models/User.php` — `posts(), comments(), media(), isAdmin(), isAuthor(), getAvatarUrlAttribute()`. Cast `password: hashed`, `email_verified_at: datetime`. Fillable: name, email, password, avatar, bio, role. **Model**
- `app/Models/Post.php` — Relasi: `user, category, tags(belongsToMany), comments, approvedComments`. Scopes: `published, featured, pending, rejected, draft, search`. Helpers: `isPending/Rejected/Draft/Published, canSubmitForReview`. Accessors: `featured_image_url, reading_time`. Booted: auto-generate slug via `Str::slug`. **Model**
- `app/Models/Category.php` — `posts(), publishedPosts()`. Booted: auto-slug. **Model**
- `app/Models/Tag.php` — `posts()`. Booted: auto-slug. **Model**
- `app/Models/Comment.php` — `post, user, parent, replies (approved only)`. Accessor `author_display_name`. Cast `is_approved: boolean`. **Model**
- `app/Models/Media.php` — `user()`. Accessors `url, size_formatted, dimensions`. **Model**
- `app/Models/Setting.php` — static `get(key, default)` + `set(key, value)` via `updateOrCreate`. Simple key-value store. **Model**

## Providers
- `app/Providers/AppServiceProvider.php` — `boot()`: `URL::forceScheme('https')` jika environment production. **Provider**

## View Components (Breeze)
- `app/View/Components/AppLayout.php` — pointer ke `layouts.app`. **View/UI**
- `app/View/Components/GuestLayout.php` — pointer ke `layouts.guest`. **View/UI**

## Views (Blade, ringkas per direktori)
- `resources/views/layouts/` — `app, guest` (Breeze), `admin, author, blog, navigation`. **View/UI**
- `resources/views/components/` — Breeze form/UI (`text-input, primary-button, modal, dropdown, ...`) + custom `media-picker`, `media-picker-modal`, `seo` (meta tags). **View/UI**
- `resources/views/home.blade.php` — landing page. **View/UI**
- `resources/views/posts/` — `index, show, by-category, by-tag`. **View/UI**
- `resources/views/pages/` — `about, contact` (static, via `Route::view`). **View/UI**
- `resources/views/admin/` — `dashboard`, `posts/{index,create,edit,ai-generate}`, `users/*`, `categories/*`, `tags/*`, `comments/index`, `media/index`. **View/UI**
- `resources/views/author/` — `dashboard`, `posts/*`, `comments/index`, `media/index`. **View/UI**
- `resources/views/auth/` — Breeze forms (login, register, forgot/reset/confirm password, verify-email). **View/UI**
- `resources/views/profile/` — `edit` + 3 partials (update-profile, update-password, delete-user). **View/UI**
- `resources/views/sitemap.blade.php` — template XML sitemap. **View/UI**

## Config (custom yang perlu diperhatikan)
- `config/services.php` — tambahan key `gemini` (api_key, model). **Config**
- `config/purifier.php` — konfigurasi HTMLPurifier. Preset `blog` custom (whitelist h2-h4, p, ul/ol, blockquote, code/pre, a, img, strong/em; target=_blank + rel=nofollow pada link; scheme hanya http/https/mailto). Dipakai oleh Admin/Author PostController + AiPostController. **Config**
- `config/database.php` — konfigurasi `pgsql` tersedia (default port 5432, sslmode `prefer`). **Config**
- `config/auth.php` — guard default `web`, provider `users` → model `App\Models\User`. **Config**

---

# Data & Config

## Lokasi Konfigurasi
- `.env` / `.env.example` — env utama. Di `.env.example`: `DB_CONNECTION=sqlite`, tapi migration `2024_01_02_000001` pakai PostgreSQL syntax (`CHECK` constraint via raw SQL). **Inconsistency yang perlu dicatat** — lihat Risks.
- `config/*.php` — 10 config standar + customisasi minimal di `services.php`.
- `bootstrap/app.php` — register middleware alias (`admin`, `author`), append `SetLocale` ke web grup, tanpa schedule / console routes.

## Konfigurasi PostgreSQL
- Driver aktif via `env('DB_CONNECTION')`. Di `.env.example` = `sqlite`, di `config/database.php` default fallback = `sqlite`.
- Koneksi `pgsql` di `config/database.php`:
  - host default `127.0.0.1`, port `5432`, database `laravel`, `search_path: public`, `sslmode: prefer`
  - charset `utf8`
- Hanya satu koneksi custom; tidak ada multi-tenant DB.

## Skema Data (13 migrasi)

| Tabel | Kolom utama | Relasi / Catatan |
|---|---|---|
| `users` | id, name, email(unique), email_verified_at, password, avatar, bio, **role enum[admin,author,reader]** default `reader`, remember_token, timestamps | Ditunjuk oleh posts, comments, media |
| `password_reset_tokens` | email(primary), token, created_at | Breeze default |
| `sessions` | id(primary), user_id(index), ip_address, user_agent, payload, last_activity(index) | Session DB-driver |
| `cache` / `cache_locks` | key(primary), value, expiration(index) | Cache DB-driver |
| `jobs` | id, queue(index), payload, attempts, reserved_at, available_at, created_at | Queue DB-driver |
| `job_batches` | id(primary), name, counts, options, timestamps | Batching |
| `failed_jobs` | id, uuid(unique), connection, queue, payload, exception, failed_at | Failed queue |
| `categories` | id, name, slug(unique), description, timestamps | `hasMany` posts |
| `tags` | id, name, slug(unique), timestamps | `belongsToMany` posts |
| `posts` | id, user_id(FK cascadeOnDelete), category_id(FK nullOnDelete), title, slug(unique), excerpt, body(longText), featured_image, **status enum[draft,published] → diubah ke CHECK CONSTRAINT {draft,pending,published,rejected}**, is_featured(bool), published_at, views_count(unsignedBigInt), timestamps. Index tambahan: status, published_at, (status, published_at) | Pivot `post_tag` |
| `post_tag` | id, post_id(FK cascade), tag_id(FK cascade), **unique(post_id, tag_id)** | M2M pivot |
| `comments` | id, post_id(FK cascade), user_id(FK nullOnDelete, nullable), author_name, author_email, body, is_approved(bool)(indexed), parent_id(FK self cascade), timestamps | Nested threading |
| `settings` | id, key(unique), value(text), timestamps | Simple KV store |
| `media` | id, user_id(FK cascade), filename, original_name, path, mime_type, size(unsigned), width, height, alt_text, timestamps | - |

## Model Inti & Relasi
```
User 1───* Post ───* Comment
 │         │         │
 │         │─── M:M Tag (post_tag pivot)
 │         │
 │         └─── N:1 Category
 │
 │──* Media
 └──* Comment (user_id nullable; guest boleh komentar)

Comment ──self_ref── Comment (parent_id, cascade)
```

## Enum / Status Penting
- `users.role`: `admin | author | reader` (enum di migration). Default `reader`.
- `posts.status`: **awalnya** `draft | published` (enum), **kemudian** di-migrate ke **PostgreSQL CHECK constraint** `draft | pending | published | rejected` plus kolom `rejection_reason` nullable.
- `comments.is_approved`: boolean; default `false`. Auto-true untuk user login.

## Migration / Seed / Factory
- Migrations: `database/migrations/*.php` (13 file)
- Seeder: `database/seeders/DatabaseSeeder.php` — membuat 4 users (admin, 2 author, 1 reader), 6 categories, 20 tags, 9 sample posts (lengkap dengan body HTML), random comments (approved + pending), 3 settings (site_name, site_description, site_email).
- Factory: hanya `database/factories/UserFactory.php` (standar Breeze).

## Runtime Artifacts
- `storage/app/private` — disk `local` (serve=true)
- `storage/app/public` — disk `public` (URL `{APP_URL}/storage`), **butuh `php artisan storage:link`**
- `storage/logs` — Monolog single channel
- `storage/framework/{cache,sessions,views}` — runtime Laravel
- Folder upload aktif: `posts/`, `avatars/`, `media/` (di dalam disk `public`)

## Queue / Cache / Session / Mail
- **Queue**: driver `database`, tabel `jobs`. Dipanggil di composer script `dev` via `php artisan queue:listen`. Tapi **tidak ada Job class** di `app/Jobs`.
- **Cache**: driver `database`, tabel `cache`.
- **Session**: driver `database`, tabel `sessions`, serialization `json`, same_site `lax`.
- **Mail**: default `log` (belum terhubung ke SMTP).
- **Broadcast**: `log`.

---

# PostgreSQL Notes

Fitur PostgreSQL yang terdeteksi:

- **Raw `ALTER TABLE ... CHECK CONSTRAINT`** di `database/migrations/2024_01_02_000001_update_posts_add_pending_rejected_status.php`:
  ```
  ALTER TABLE posts DROP CONSTRAINT IF EXISTS posts_status_check;
  ALTER TABLE posts ADD CONSTRAINT posts_status_check CHECK (status::text = ANY (ARRAY['draft','pending','published','rejected']));
  ```
  Cast `::text` dan sintaks `ANY (ARRAY[...])` khas PostgreSQL. Migration ini **akan gagal** di SQLite/MySQL.
- `config/database.php` koneksi `pgsql`: `search_path: public`, `sslmode: prefer`.
- Index gabungan pada `posts(status, published_at)` via Blueprint — portable, tapi dibuat untuk mempercepat listing published newest-first.
- Foreign key cascade/restrict (portable, tapi eksplisit):
  - `posts.user_id` cascade on delete
  - `posts.category_id` null on delete
  - `comments.post_id` cascade, `comments.user_id` nullOnDelete, `comments.parent_id` cascade
  - `post_tag.*` cascade; unique pair
  - `media.user_id` cascade

Fitur PG lain yang **tidak terpakai**: UUID column, JSONB, array column, full-text search (TSVECTOR), partitioning, materialized views.

Raw query lain (bukan PG-specific, kompatibel MySQL/SQLite):
- `Author\DashboardController@index` memakai `DB::raw("COUNT(CASE WHEN status = 'published' THEN 1 END)")` — SQL standar.

---

# External Integrations

| Layanan | Dipakai di | Konfigurasi |
|---|---|---|
| **Google Gemini API** | `app/Services/GeminiService.php` (via `Http::post`) | `config/services.php → gemini.api_key`, `gemini.model` (env: `GEMINI_API_KEY`, `GEMINI_MODEL`; default model `gemini-2.5-flash`) |
| **Unsplash (placeholder image)** | `Post::getFeaturedImageUrlAttribute()` fallback | Hardcoded URL `images.unsplash.com/photo-1499750310107-5fef28a66643` |
| **ui-avatars.com** | `User::getAvatarUrlAttribute()` fallback | Hardcoded URL |
| AWS S3 | `config/filesystems.php` disk `s3` (tersedia tapi tidak di-default-kan) | env `AWS_*` |
| Mail providers (SES/Postmark/Resend) | `config/services.php`, `config/mail.php` | Tersedia tapi default `log` |
| Slack | `config/services.php → slack.notifications` | Tidak dipakai di kode |

**Tidak ada**: payment gateway, webhook endpoint, push notification, Pusher/Soketi, Sentry, Telegram, dll.

---

# Security & Access Control

## Guards & Providers
- Guard default: `web` (session). Satu-satunya guard.
- Provider: `users` via Eloquent → `App\Models\User`.
- Password broker: `users` dengan tabel `password_reset_tokens`, expiry 60 menit, throttle 60 detik.
- Password confirmation timeout: 3 jam (10800 s).

## Role System (manual, bukan package)
- Kolom `users.role` enum `admin|author|reader`.
- Cek via method model: `User::isAdmin()`, `User::isAuthor()` (admin dihitung author juga).
- Tidak ada Spatie Permission / Bouncer / Silber.

## Middleware Alias
- `admin` → `AdminMiddleware` (butuh `isAdmin()`)
- `author` → `AuthorMiddleware` (butuh `isAuthor()`)
- `auth`, `guest`, `signed`, `throttle:*`, `verified` — standar Laravel/Breeze.

## Route Protection Summary
- Public: `/`, `/blog*`, `/category/*`, `/tag/*`, `/about`, `/contact`, `/sitemap.xml`, `/robots.txt`, `/locale/*`
- Throttled: `POST /blog/{post}/comments` (6/min), `verification.verify` (signed + 6,1), `verification.send` (6,1)
- `auth`: `/profile/*`, verify-email, confirm-password, password update, logout
- `guest`: register, login, password reset
- `auth,author` prefix `/author`
- `auth,admin` prefix `/admin`

## Policy Usage
- `PostPolicy` dipakai hanya di `Author\PostController` (`edit, update, destroy, submitForReview` memanggil `$this->authorize`).
- `Admin\PostController` **tidak memanggil** policy — mengandalkan middleware `admin` saja.
- `Author\CommentController::approve/destroy` dan `Author\MediaController::destroy` mengecek ownership manual (`abort(403)` bila `$medium->user_id !== auth()->id()`), bukan via policy.

## Validasi Penting
- `LoginRequest` — rate limit 5 attempts, keyed by lowercased email + IP.
- `RegisteredUserController` — email `lowercase`, `unique:users`; password pakai `Rules\Password::defaults()`.
- `ProfileUpdateRequest` — email unique ignore self.
- Upload image/media: MIME whitelist (`jpeg,png,jpg,gif,webp` — SVG sengaja dilarang), size max 2MB (avatar/featured) atau 5MB (media library), max 10 files per request.
- `featured_image_path` (media picker) divalidasi via `exists:media,path`; di Author controller ditambah `where('user_id', auth()->id())` agar hanya bisa refer media milik sendiri.
- `Post::body` disanitasi via `Purifier::clean(..., 'blog')` di Admin/Author PostController + AiPostController sebelum simpan ke DB.
- Komentar: rate-limit 6/menit + validasi body 3..1000 chars.
- AI generate: topic max 500, language whitelist `id|en`.

## Area Risiko Keamanan (observasi, bukan rewrite kode)
- `robots.txt` di-generate inline via closure, bukan file statis. OK.
- `Admin\PostController` tidak menggunakan `PostPolicy`. Admin middleware sudah cukup secara fungsional, tapi berpotensi inkonsistensi (admin bisa bypass policy rule yang mungkin ditambah nanti).
- `CommentController::store` — validasi email guest tidak disimpan unik; tidak ada bot protection (captcha/honeypot), hanya throttle 6/menit.
- `AppServiceProvider::boot` memaksa HTTPS hanya saat `env == 'production'`. Pastikan `APP_ENV` di-set benar.
- `GeminiService` mengekspos pesan error API langsung ke user (`back()->with('error', $e->getMessage())`). Bisa bocorkan detail API key jika response Gemini tidak dibersihkan.
- Security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy, HSTS) belum di-set; belum ada CSP.

---

# Risks / Blind Spots

- **Ketidakkonsistenan DB driver**:
  - `.env.example` → `DB_CONNECTION=sqlite`
  - Migration `2024_01_02_000001` pakai PostgreSQL raw SQL (`CHECK` constraint dengan `::text` cast). Migration ini akan gagal di SQLite/MySQL.
  - `composer.json` script `post-create-project-cmd` masih membuat `database/database.sqlite` dan `migrate --graceful`.
  - → Saat boot ulang di env baru: ada kemungkinan migration sebagian jalan, sebagian fail. Tidak bisa dipetakan tanpa info env aktif production.
- **`queue:listen` ada di script dev, tapi tidak ada Job class** di `app/Jobs/`. Artinya saat ini queue kosong. Future integrations (misal async Gemini call) belum ada.
- **Tidak ada scheduler** di `bootstrap/app.php` (tidak ada `withSchedule`), dan `routes/console.php` hanya berisi command `inspire`. Tidak ada scheduled task.
- **Tidak ada API routes** (`routes/api.php` tidak ada, tidak didaftarkan di `bootstrap/app.php`). `MediaController::json` & `CommentController` return JSON tetapi melalui web route.
- **`rejection_reason`** ditambahkan di migration `2024_01_02_000001` via `Schema::table(..., after('status'))`. `after()` valid di MySQL tapi diabaikan di PostgreSQL/SQLite — aman, tapi perlu diperhatikan.
- **`OPTIMIZATION-REPORT.md`** ada di root — belum dibaca; mungkin memuat catatan perubahan yang relevan (tidak masuk scope peta saat ini).
- **Route `/robots.txt`** bentrok dengan file statis di `public/robots.txt` bawaan Laravel. Yang akan menang: file statis di webserver (karena di-serve sebelum PHP). Route closure `->name` tidak terpanggil di environment dengan `public/robots.txt`. **Perlu dicek manual** apakah file `public/robots.txt` masih ada.
- **Blade `media-picker`** di views tapi implementasi JS-nya tidak bisa diverifikasi tanpa membaca file blade. Diasumsikan Alpine.js-based.
- **Locale switch** hanya terima `id|en`. Folder `lang/` ada tapi isinya tidak di-scan — kemungkinan berisi translasi.
- **Dynamic route resolution**: tidak ada. Semua route statis.
- **Macro / magic method**: tidak ditemukan di kode aplikasi; hanya Eloquent standar (scopes, accessors, boot hooks).
- **Package behavior di `vendor/`**: tidak dibaca. Breeze controllers tidak dimodifikasi dari default — perilaku sesuai dokumentasi Laravel 13 + Breeze 2.4.
