<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Author;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// ==========================================
// PUBLIC ROUTES
// ==========================================
Route::get('/', [HomeController::class, 'index'])->name('home');

// Blog Posts
Route::get('/blog', [PostController::class, 'index'])->name('posts.index');
Route::get('/blog/{post:slug}', [PostController::class, 'show'])->name('posts.show');

// Category & Tag
Route::get('/category/{category:slug}', [PostController::class, 'byCategory'])->name('posts.category');
Route::get('/tag/{tag:slug}', [PostController::class, 'byTag'])->name('posts.tag');

// Comments
Route::post('/blog/{post}/comments', [CommentController::class, 'store'])->name('comments.store');

// Static Pages
Route::view('/about', 'pages.about')->name('about');
Route::view('/contact', 'pages.contact')->name('contact');

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    $content = "User-agent: *\nAllow: /\n\nSitemap: " . url('/sitemap.xml') . "\n\nDisallow: /admin\nDisallow: /author\nDisallow: /profile\n";

    return response($content, 200)->header('Content-Type', 'text/plain');
});

// Language Switch
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['id', 'en'])) {
        session()->put('locale', $locale);
    }

    return redirect()->back();
})->name('locale.switch');

// ==========================================
// AUTH ROUTES (Breeze)
// ==========================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ==========================================
// AUTHOR ROUTES
// ==========================================
Route::middleware(['auth', 'author'])->prefix('author')->name('author.')->group(function () {
    Route::get('/', [Author\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('posts', Author\PostController::class);
    Route::patch('posts/{post}/submit', [Author\PostController::class, 'submitForReview'])->name('posts.submit');

    Route::get('comments', [Author\CommentController::class, 'index'])->name('comments.index');
    Route::patch('comments/{comment}/approve', [Author\CommentController::class, 'approve'])->name('comments.approve');
    Route::delete('comments/{comment}', [Author\CommentController::class, 'destroy'])->name('comments.destroy');
});

// ==========================================
// ADMIN ROUTES
// ==========================================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Posts
    Route::resource('posts', Admin\PostController::class)->except(['show']);
    Route::patch('posts/{post}/approve', [Admin\PostController::class, 'approve'])->name('posts.approve');
    Route::patch('posts/{post}/reject', [Admin\PostController::class, 'reject'])->name('posts.reject');

    // AI Post Generator
    Route::get('ai-posts/create', [Admin\AiPostController::class, 'create'])->name('ai-posts.create');
    Route::post('ai-posts/generate', [Admin\AiPostController::class, 'generate'])->name('ai-posts.generate');
    Route::post('ai-posts', [Admin\AiPostController::class, 'store'])->name('ai-posts.store');

    // Categories
    Route::resource('categories', Admin\CategoryController::class)->except(['show']);

    // Tags
    Route::resource('tags', Admin\TagController::class)->except(['show']);

    // Comments
    Route::get('comments', [Admin\CommentController::class, 'index'])->name('comments.index');
    Route::patch('comments/{comment}/approve', [Admin\CommentController::class, 'approve'])->name('comments.approve');
    Route::patch('comments/{comment}/reject', [Admin\CommentController::class, 'reject'])->name('comments.reject');
    Route::delete('comments/{comment}', [Admin\CommentController::class, 'destroy'])->name('comments.destroy');

    // Users
    Route::resource('users', Admin\UserController::class)->except(['show']);
});

require __DIR__ . '/auth.php';
