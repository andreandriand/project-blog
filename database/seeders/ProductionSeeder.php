<?php

/*
 * Tujuan: Seeder aman untuk production - hanya data referensi (categories, tags, settings).
 * Caller: php artisan db:seed --class=ProductionSeeder
 * Dependensi: Models Category, Tag, Setting.
 * Main Functions: run() - idempotent (firstOrCreate), aman dijalankan berulang.
 * Side Effects: Insert/skip ke tabel categories, tags, settings.
 *
 * Catatan: Jangan pernah jalankan DatabaseSeeder default di production -
 * ia membuat 4 user dummy dengan password "password" (lihat database/seeders/DatabaseSeeder.php).
 * Pakai seeder ini untuk bootstrap reference data, lalu buat admin user manual via tinker:
 *   php artisan tinker
 *   User::create(['name' => 'Admin', 'email' => '...', 'password' => '...', 'role' => 'admin']);
 */

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Teknologi', 'slug' => 'teknologi', 'description' => 'Artikel seputar perkembangan teknologi terkini'],
            ['name' => 'Web Development', 'slug' => 'web-development', 'description' => 'Tutorial dan tips pengembangan web'],
            ['name' => 'Desain', 'slug' => 'desain', 'description' => 'Inspirasi dan panduan desain grafis & UI/UX'],
            ['name' => 'Produktivitas', 'slug' => 'produktivitas', 'description' => 'Tips meningkatkan produktivitas kerja'],
            ['name' => 'Karir', 'slug' => 'karir', 'description' => 'Panduan pengembangan karir di dunia IT'],
            ['name' => 'Tutorial', 'slug' => 'tutorial', 'description' => 'Panduan langkah demi langkah'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['slug' => $category['slug']], $category);
        }

        $tags = [
            'Laravel', 'PHP', 'JavaScript', 'Vue.js', 'React', 'Tailwind CSS',
            'PostgreSQL', 'MySQL', 'API', 'Docker', 'Git', 'Linux',
            'UI/UX', 'Figma', 'CSS', 'HTML', 'TypeScript', 'Node.js',
            'Tips', 'Best Practice',
        ];

        foreach ($tags as $name) {
            Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        Setting::set('site_name', config('app.name', 'AndBlog'));
        Setting::set('site_description', 'Platform blog modern untuk berbagi ide dan pengetahuan');
        Setting::set('site_email', config('mail.from.address', 'hello@example.com'));
    }
}
