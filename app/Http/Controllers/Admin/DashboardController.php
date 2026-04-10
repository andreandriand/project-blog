<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_posts' => Post::count(),
            'published_posts' => Post::where('status', 'published')->count(),
            'total_comments' => Comment::count(),
            'pending_comments' => Comment::where('is_approved', false)->count(),
            'total_views' => Post::sum('views_count'),
            'total_users' => User::count(),
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
