<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // USERS
        // ==========================================
        $admin = User::create([
            'name' => 'Admin Blog',
            'email' => 'admin@blog.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'bio' => 'Administrator dan penulis utama di ModernBlog. Passionate tentang teknologi dan desain.',
            'email_verified_at' => now(),
        ]);

        $author1 = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@blog.com',
            'password' => bcrypt('password'),
            'role' => 'author',
            'bio' => 'Full-stack developer dengan pengalaman 5 tahun. Suka berbagi pengetahuan tentang web development.',
            'email_verified_at' => now(),
        ]);

        $author2 = User::create([
            'name' => 'Sari Dewi',
            'email' => 'sari@blog.com',
            'password' => bcrypt('password'),
            'role' => 'author',
            'bio' => 'UI/UX Designer yang gemar menulis tentang desain dan kreativitas.',
            'email_verified_at' => now(),
        ]);

        $reader = User::create([
            'name' => 'Andi Pratama',
            'email' => 'andi@blog.com',
            'password' => bcrypt('password'),
            'role' => 'reader',
            'email_verified_at' => now(),
        ]);

        // ==========================================
        // CATEGORIES
        // ==========================================
        $categories = collect([
            ['name' => 'Teknologi', 'slug' => 'teknologi', 'description' => 'Artikel seputar perkembangan teknologi terkini'],
            ['name' => 'Web Development', 'slug' => 'web-development', 'description' => 'Tutorial dan tips pengembangan web'],
            ['name' => 'Desain', 'slug' => 'desain', 'description' => 'Inspirasi dan panduan desain grafis & UI/UX'],
            ['name' => 'Produktivitas', 'slug' => 'produktivitas', 'description' => 'Tips meningkatkan produktivitas kerja'],
            ['name' => 'Karir', 'slug' => 'karir', 'description' => 'Panduan pengembangan karir di dunia IT'],
            ['name' => 'Tutorial', 'slug' => 'tutorial', 'description' => 'Panduan langkah demi langkah'],
        ])->map(fn($cat) => Category::create($cat));

        // ==========================================
        // TAGS
        // ==========================================
        $tags = collect([
            'Laravel', 'PHP', 'JavaScript', 'Vue.js', 'React', 'Tailwind CSS',
            'PostgreSQL', 'MySQL', 'API', 'Docker', 'Git', 'Linux',
            'UI/UX', 'Figma', 'CSS', 'HTML', 'TypeScript', 'Node.js',
            'Tips', 'Best Practice',
        ])->map(fn($name) => Tag::create(['name' => $name, 'slug' => Str::slug($name)]));

        // ==========================================
        // POSTS
        // ==========================================
        $posts = [
            [
                'user_id' => $admin->id,
                'category_id' => $categories[1]->id, // Web Development
                'title' => 'Memulai dengan Laravel 13: Panduan Lengkap untuk Pemula',
                'slug' => 'memulai-dengan-laravel-13-panduan-lengkap',
                'excerpt' => 'Pelajari cara memulai proyek web dengan Laravel 13, framework PHP paling populer. Dari instalasi hingga deployment.',
                'body' => '<h2>Apa itu Laravel?</h2>
<p>Laravel adalah framework PHP yang elegan dan ekspresif, dirancang untuk membuat pengembangan web menjadi lebih menyenangkan dan produktif. Dengan rilis versi 13, Laravel membawa banyak fitur baru yang memudahkan developer.</p>

<h2>Instalasi Laravel 13</h2>
<p>Untuk memulai, pastikan Anda memiliki PHP 8.3+ dan Composer terinstal di sistem Anda. Kemudian jalankan perintah berikut:</p>

<pre><code>composer create-project laravel/laravel my-project "13.*"</code></pre>

<h2>Fitur Baru di Laravel 13</h2>
<ul>
<li><strong>Improved Performance</strong> - Laravel 13 hadir dengan peningkatan performa yang signifikan</li>
<li><strong>Better Type Safety</strong> - Dukungan type safety yang lebih baik</li>
<li><strong>New Artisan Commands</strong> - Perintah artisan baru yang memudahkan development</li>
<li><strong>Enhanced Testing</strong> - Framework testing yang lebih powerful</li>
</ul>

<h2>Struktur Proyek</h2>
<p>Laravel menggunakan arsitektur MVC (Model-View-Controller) yang memisahkan logika bisnis, presentasi, dan data. Ini membuat kode lebih terorganisir dan mudah dimaintain.</p>

<blockquote>Laravel membuat pengembangan web menjadi seni, bukan sekadar pekerjaan.</blockquote>

<h2>Kesimpulan</h2>
<p>Laravel 13 adalah pilihan tepat untuk proyek web modern. Dengan ekosistem yang kaya dan komunitas yang aktif, Anda akan menemukan semua yang dibutuhkan untuk membangun aplikasi web yang luar biasa.</p>',
                'status' => 'published',
                'is_featured' => true,
                'published_at' => now()->subDays(1),
                'views_count' => 1250,
            ],
            [
                'user_id' => $author1->id,
                'category_id' => $categories[2]->id, // Desain
                'title' => 'Tailwind CSS 4: Revolusi dalam Styling Modern',
                'slug' => 'tailwind-css-4-revolusi-styling-modern',
                'excerpt' => 'Tailwind CSS 4 membawa perubahan besar dalam cara kita menulis CSS. Pelajari fitur-fitur baru yang game-changing.',
                'body' => '<h2>Mengapa Tailwind CSS?</h2>
<p>Tailwind CSS telah mengubah cara developer menulis CSS. Dengan pendekatan utility-first, Anda bisa membangun desain yang indah tanpa meninggalkan HTML.</p>

<h2>Fitur Baru di Tailwind CSS 4</h2>
<p>Versi 4 membawa banyak peningkatan:</p>
<ul>
<li><strong>Lightning CSS Engine</strong> - Build time yang jauh lebih cepat</li>
<li><strong>CSS-first Configuration</strong> - Konfigurasi langsung di CSS</li>
<li><strong>Improved Dark Mode</strong> - Dukungan dark mode yang lebih baik</li>
<li><strong>Container Queries</strong> - Responsive design yang lebih fleksibel</li>
</ul>

<h2>Dark Mode dengan Tailwind</h2>
<p>Implementasi dark mode menjadi sangat mudah dengan Tailwind CSS. Cukup tambahkan prefix <code>dark:</code> pada class yang ingin Anda ubah saat dark mode aktif.</p>

<pre><code>&lt;div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white"&gt;
  Konten yang responsif terhadap dark mode
&lt;/div&gt;</code></pre>

<h2>Tips Produktivitas</h2>
<p>Gunakan extension Tailwind CSS IntelliSense di VS Code untuk autocomplete yang lebih cepat. Ini akan sangat meningkatkan produktivitas Anda.</p>',
                'status' => 'published',
                'is_featured' => true,
                'published_at' => now()->subDays(2),
                'views_count' => 890,
            ],
            [
                'user_id' => $author2->id,
                'category_id' => $categories[0]->id, // Teknologi
                'title' => 'PostgreSQL vs MySQL: Mana yang Lebih Baik untuk Proyek Anda?',
                'slug' => 'postgresql-vs-mysql-perbandingan-lengkap',
                'excerpt' => 'Perbandingan mendalam antara PostgreSQL dan MySQL. Temukan database yang tepat untuk kebutuhan proyek Anda.',
                'body' => '<h2>Pendahuluan</h2>
<p>Memilih database yang tepat adalah keputusan penting dalam pengembangan aplikasi. Dua pilihan paling populer adalah PostgreSQL dan MySQL. Mari kita bandingkan keduanya.</p>

<h2>PostgreSQL</h2>
<p>PostgreSQL dikenal sebagai database yang paling advanced. Beberapa keunggulannya:</p>
<ul>
<li>Dukungan JSON/JSONB yang excellent</li>
<li>Full-text search bawaan</li>
<li>Extensibility yang luar biasa</li>
<li>ACID compliance yang ketat</li>
<li>Dukungan untuk data types yang kompleks</li>
</ul>

<h2>MySQL</h2>
<p>MySQL adalah database paling populer di dunia. Keunggulannya:</p>
<ul>
<li>Mudah dipelajari dan digunakan</li>
<li>Performa read yang sangat cepat</li>
<li>Ekosistem hosting yang luas</li>
<li>Komunitas yang besar</li>
</ul>

<h2>Kapan Menggunakan PostgreSQL?</h2>
<p>Pilih PostgreSQL jika proyek Anda membutuhkan query yang kompleks, data integrity yang ketat, atau fitur-fitur advanced seperti window functions dan CTE.</p>

<h2>Kesimpulan</h2>
<p>Tidak ada jawaban yang benar atau salah. Pilihan tergantung pada kebutuhan spesifik proyek Anda. Untuk proyek modern dengan kebutuhan yang kompleks, PostgreSQL sering menjadi pilihan yang lebih baik.</p>',
                'status' => 'published',
                'is_featured' => true,
                'published_at' => now()->subDays(3),
                'views_count' => 2100,
            ],
            [
                'user_id' => $admin->id,
                'category_id' => $categories[3]->id, // Produktivitas
                'title' => '10 Tips Produktivitas untuk Developer di Tahun 2026',
                'slug' => '10-tips-produktivitas-developer-2026',
                'excerpt' => 'Tingkatkan produktivitas coding Anda dengan tips-tips praktis yang bisa langsung diterapkan.',
                'body' => '<h2>1. Gunakan Keyboard Shortcuts</h2>
<p>Menguasai keyboard shortcuts di IDE favorit Anda bisa menghemat berjam-jam waktu setiap minggu. Mulai dari navigasi file hingga refactoring code.</p>

<h2>2. Terapkan Pomodoro Technique</h2>
<p>Bekerja dalam interval 25 menit dengan istirahat 5 menit. Setelah 4 sesi, ambil istirahat lebih panjang 15-30 menit.</p>

<h2>3. Automate Repetitive Tasks</h2>
<p>Jika Anda melakukan sesuatu lebih dari dua kali, pertimbangkan untuk mengotomatisasinya. Gunakan scripts, aliases, atau tools seperti Make.</p>

<h2>4. Code Review Secara Rutin</h2>
<p>Code review bukan hanya untuk menemukan bug, tapi juga untuk belajar dari rekan kerja dan meningkatkan kualitas kode secara keseluruhan.</p>

<h2>5. Jaga Kesehatan</h2>
<p>Produktivitas dimulai dari tubuh yang sehat. Pastikan Anda cukup tidur, berolahraga, dan makan dengan baik.</p>

<h2>6. Gunakan Version Control dengan Benar</h2>
<p>Commit sering, tulis pesan commit yang jelas, dan gunakan branching strategy yang konsisten.</p>

<h2>7. Dokumentasikan Kode Anda</h2>
<p>Kode yang terdokumentasi dengan baik menghemat waktu di masa depan, baik untuk Anda sendiri maupun tim.</p>

<h2>8. Belajar Terus-Menerus</h2>
<p>Dunia teknologi bergerak cepat. Sisihkan waktu setiap minggu untuk belajar hal baru.</p>

<h2>9. Gunakan Tools yang Tepat</h2>
<p>Investasikan waktu untuk menemukan dan menguasai tools yang tepat untuk workflow Anda.</p>

<h2>10. Istirahat yang Cukup</h2>
<p>Burnout adalah musuh produktivitas. Jangan ragu untuk mengambil istirahat ketika dibutuhkan.</p>',
                'status' => 'published',
                'is_featured' => false,
                'published_at' => now()->subDays(4),
                'views_count' => 750,
            ],
            [
                'user_id' => $author1->id,
                'category_id' => $categories[5]->id, // Tutorial
                'title' => 'Membuat REST API dengan Laravel: Panduan Step-by-Step',
                'slug' => 'membuat-rest-api-laravel-panduan-step-by-step',
                'excerpt' => 'Tutorial lengkap membuat REST API yang scalable dan secure menggunakan Laravel.',
                'body' => '<h2>Persiapan</h2>
<p>Sebelum memulai, pastikan Anda sudah memiliki proyek Laravel yang terinstal. Kita akan membuat API untuk manajemen produk.</p>

<h2>Membuat Model dan Migration</h2>
<pre><code>php artisan make:model Product -m</code></pre>

<h2>Membuat Controller</h2>
<pre><code>php artisan make:controller Api/ProductController --api</code></pre>

<h2>Mendefinisikan Routes</h2>
<p>Tambahkan routes API di file <code>routes/api.php</code>:</p>
<pre><code>Route::apiResource("products", ProductController::class);</code></pre>

<h2>Implementasi CRUD</h2>
<p>Implementasikan method index, store, show, update, dan destroy di controller Anda. Gunakan Form Request untuk validasi dan API Resource untuk transformasi response.</p>

<h2>Authentication</h2>
<p>Gunakan Laravel Sanctum untuk autentikasi API. Ini memberikan sistem token yang ringan dan mudah digunakan.</p>

<h2>Testing</h2>
<p>Jangan lupa menulis test untuk API Anda. Laravel menyediakan tools testing yang powerful untuk memastikan API berfungsi dengan benar.</p>',
                'status' => 'published',
                'is_featured' => false,
                'published_at' => now()->subDays(5),
                'views_count' => 1500,
            ],
            [
                'user_id' => $author2->id,
                'category_id' => $categories[2]->id, // Desain
                'title' => 'Prinsip UI/UX Design yang Wajib Diketahui Setiap Developer',
                'slug' => 'prinsip-ui-ux-design-wajib-diketahui',
                'excerpt' => 'Memahami prinsip dasar UI/UX design akan membuat Anda menjadi developer yang lebih baik.',
                'body' => '<h2>Mengapa Developer Perlu Memahami UI/UX?</h2>
<p>Sebagai developer, memahami prinsip UI/UX membantu Anda membuat keputusan yang lebih baik saat mengimplementasikan desain dan berkomunikasi dengan tim desain.</p>

<h2>1. Consistency</h2>
<p>Konsistensi dalam desain menciptakan pengalaman yang familiar dan mudah diprediksi oleh pengguna. Gunakan design system untuk menjaga konsistensi.</p>

<h2>2. Hierarchy</h2>
<p>Visual hierarchy membantu pengguna memahami informasi mana yang paling penting. Gunakan ukuran, warna, dan spacing untuk menciptakan hierarchy yang jelas.</p>

<h2>3. Feedback</h2>
<p>Setiap aksi pengguna harus mendapat feedback. Loading states, success messages, dan error handling yang baik meningkatkan user experience.</p>

<h2>4. Accessibility</h2>
<p>Desain yang accessible adalah desain yang baik. Pastikan aplikasi Anda bisa digunakan oleh semua orang, termasuk pengguna dengan disabilitas.</p>

<h2>5. Simplicity</h2>
<p>Less is more. Hindari complexity yang tidak perlu dan fokus pada fitur yang benar-benar dibutuhkan pengguna.</p>',
                'status' => 'published',
                'is_featured' => false,
                'published_at' => now()->subDays(6),
                'views_count' => 680,
            ],
            [
                'user_id' => $admin->id,
                'category_id' => $categories[4]->id, // Karir
                'title' => 'Roadmap Menjadi Full-Stack Developer di 2026',
                'slug' => 'roadmap-full-stack-developer-2026',
                'excerpt' => 'Panduan lengkap untuk memulai karir sebagai full-stack developer. Dari dasar hingga advanced.',
                'body' => '<h2>Apa itu Full-Stack Developer?</h2>
<p>Full-stack developer adalah developer yang mampu bekerja di sisi frontend maupun backend. Mereka memahami keseluruhan stack teknologi yang digunakan dalam pengembangan web.</p>

<h2>Frontend Skills</h2>
<ul>
<li>HTML, CSS, JavaScript (fundamental)</li>
<li>Framework: React, Vue.js, atau Svelte</li>
<li>CSS Framework: Tailwind CSS</li>
<li>State Management</li>
<li>Testing (Jest, Cypress)</li>
</ul>

<h2>Backend Skills</h2>
<ul>
<li>PHP dengan Laravel atau Node.js</li>
<li>Database: PostgreSQL, MySQL</li>
<li>REST API & GraphQL</li>
<li>Authentication & Authorization</li>
<li>Caching & Queue</li>
</ul>

<h2>DevOps Basics</h2>
<ul>
<li>Git & GitHub</li>
<li>Docker</li>
<li>CI/CD</li>
<li>Cloud Services (AWS, GCP, atau DigitalOcean)</li>
</ul>

<h2>Tips Sukses</h2>
<p>Fokus pada fundamental terlebih dahulu. Jangan terburu-buru mempelajari semua framework sekaligus. Bangun proyek nyata untuk mengasah skill Anda.</p>',
                'status' => 'published',
                'is_featured' => false,
                'published_at' => now()->subDays(7),
                'views_count' => 3200,
            ],
            [
                'user_id' => $author1->id,
                'category_id' => $categories[1]->id, // Web Development
                'title' => 'Alpine.js: Framework JavaScript Ringan untuk Interaktivitas',
                'slug' => 'alpine-js-framework-javascript-ringan',
                'excerpt' => 'Kenali Alpine.js, framework JavaScript minimalis yang sempurna untuk menambahkan interaktivitas pada halaman web.',
                'body' => '<h2>Apa itu Alpine.js?</h2>
<p>Alpine.js adalah framework JavaScript ringan yang menawarkan sifat reaktif dan deklaratif seperti Vue.js atau React, tapi dengan footprint yang jauh lebih kecil.</p>

<h2>Kapan Menggunakan Alpine.js?</h2>
<p>Alpine.js sempurna untuk:</p>
<ul>
<li>Dropdown menus dan modals</li>
<li>Tab navigation</li>
<li>Toggle dark/light mode</li>
<li>Form validation sederhana</li>
<li>Interaktivitas ringan tanpa build step</li>
</ul>

<h2>Contoh Penggunaan</h2>
<pre><code>&lt;div x-data="{ open: false }"&gt;
    &lt;button @click="open = !open"&gt;Toggle&lt;/button&gt;
    &lt;div x-show="open" x-transition&gt;
        Konten yang bisa di-toggle
    &lt;/div&gt;
&lt;/div&gt;</code></pre>

<h2>Alpine.js + Laravel</h2>
<p>Kombinasi Alpine.js dengan Laravel Blade adalah match yang sempurna. Anda mendapatkan server-side rendering dengan interaktivitas client-side tanpa complexity dari SPA.</p>',
                'status' => 'published',
                'is_featured' => false,
                'published_at' => now()->subDays(8),
                'views_count' => 520,
            ],
            [
                'user_id' => $admin->id,
                'category_id' => $categories[0]->id, // Teknologi
                'title' => 'Tren Teknologi Web yang Harus Diperhatikan di 2026',
                'slug' => 'tren-teknologi-web-2026',
                'excerpt' => 'Draft artikel tentang tren teknologi web terbaru.',
                'body' => '<h2>Coming Soon</h2><p>Artikel ini masih dalam proses penulisan. Stay tuned!</p>',
                'status' => 'draft',
                'is_featured' => false,
                'published_at' => null,
                'views_count' => 0,
            ],
        ];

        $tagMapping = [
            0 => [0, 1, 5], // Laravel article -> Laravel, PHP, Tailwind CSS
            1 => [5, 14, 15], // Tailwind article -> Tailwind CSS, CSS, HTML
            2 => [6, 7], // PostgreSQL article -> PostgreSQL, MySQL
            3 => [18, 19], // Productivity -> Tips, Best Practice
            4 => [0, 1, 8], // REST API -> Laravel, PHP, API
            5 => [12, 13, 14], // UI/UX -> UI/UX, Figma, CSS
            6 => [2, 4, 5, 17], // Full-stack -> JavaScript, React, Tailwind, Node.js
            7 => [2, 0, 5], // Alpine.js -> JavaScript, Laravel, Tailwind
        ];

        foreach ($posts as $index => $postData) {
            $post = Post::create($postData);
            if (isset($tagMapping[$index])) {
                $tagIds = collect($tagMapping[$index])->map(fn($i) => $tags[$i]->id)->toArray();
                $post->tags()->sync($tagIds);
            }
        }

        // ==========================================
        // COMMENTS
        // ==========================================
        $publishedPosts = Post::where('status', 'published')->get();

        $commentTexts = [
            'Artikel yang sangat informatif! Terima kasih sudah berbagi.',
            'Penjelasannya sangat jelas dan mudah dipahami. Mantap!',
            'Saya sudah mencoba tutorial ini dan berhasil. Terima kasih!',
            'Kapan ada artikel lanjutannya? Sangat menantikan!',
            'Ini sangat membantu untuk proyek yang sedang saya kerjakan.',
            'Bagus sekali artikelnya, sangat bermanfaat untuk pemula seperti saya.',
            'Apakah ada rekomendasi resource tambahan untuk topik ini?',
            'Saya setuju dengan poin-poin yang disampaikan. Great article!',
        ];

        foreach ($publishedPosts->take(5) as $post) {
            // Approved comments
            for ($i = 0; $i < rand(2, 4); $i++) {
                Comment::create([
                    'post_id' => $post->id,
                    'user_id' => collect([$reader->id, $author1->id, $author2->id, null])->random(),
                    'author_name' => collect([null, 'Rizky', 'Maya', 'Dian', 'Fajar'])->random(),
                    'author_email' => collect([null, 'rizky@email.com', 'maya@email.com', 'dian@email.com'])->random(),
                    'body' => $commentTexts[array_rand($commentTexts)],
                    'is_approved' => true,
                    'created_at' => now()->subDays(rand(0, 5)),
                ]);
            }

            // Pending comment
            Comment::create([
                'post_id' => $post->id,
                'author_name' => 'Guest User',
                'author_email' => 'guest@email.com',
                'body' => 'Komentar ini menunggu persetujuan moderator.',
                'is_approved' => false,
                'created_at' => now()->subHours(rand(1, 24)),
            ]);
        }

        // ==========================================
        // SETTINGS
        // ==========================================
        Setting::set('site_name', 'ModernBlog');
        Setting::set('site_description', 'Platform blog modern untuk berbagi ide dan pengetahuan');
        Setting::set('site_email', 'hello@modernblog.com');
    }
}
