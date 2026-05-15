<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

/**
 * Tujuan: Halaman dashboard admin — agregasi statistik global (posts, comments, views, users).
 * Caller: routes/web.php grup admin -> admin.dashboard -> Admin\DashboardController@index.
 * Dependensi: Models Post, Comment, User.
 * Main Functions: index() — render `admin.dashboard` dengan 5 key stats + 5 recent posts/comments.
 * Side Effects: Read-only — 5 query DB (post stats agregat, comment stats agregat, user count, recent posts, recent comments).
 *
 * Catatan performa: agregasi posts dan comments di-konsolidasi jadi 1 query masing-masing
 * memakai sintaks PostgreSQL `COUNT(*) FILTER (WHERE ...)` (lebih ringkas dari `SUM(CASE ...)`).
 * Sebelumnya: 6 query agregasi terpisah. Sekarang: 2 query agregasi + 1 count + 2 recent list.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $postStats = Post::selectRaw("
            COUNT(*) AS total_posts,
            COUNT(*) FILTER (WHERE status = 'published') AS published_posts,
            COALESCE(SUM(views_count), 0) AS total_views
        ")->first();

        $commentStats = Comment::selectRaw('
            COUNT(*) AS total_comments,
            COUNT(*) FILTER (WHERE is_approved = false) AS pending_comments
        ')->first();

        $stats = [
            'total_posts'      => (int) $postStats->total_posts,
            'published_posts'  => (int) $postStats->published_posts,
            'total_views'      => (int) $postStats->total_views,
            'total_comments'   => (int) $commentStats->total_comments,
            'pending_comments' => (int) $commentStats->pending_comments,
            'total_users'      => User::count(),
        ];

        $recentPosts = Post::with(['user', 'category'])
            ->latest()
            ->take(5)
            ->get();

        $recentComments = Comment::with(['post', 'user'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentPosts', 'recentComments'));
    }
}
