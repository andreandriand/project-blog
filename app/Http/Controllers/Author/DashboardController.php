<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $stats = [
            'total_posts' => Post::where('user_id', $userId)->count(),
            'published_posts' => Post::where('user_id', $userId)->where('status', 'published')->count(),
            'pending_posts' => Post::where('user_id', $userId)->where('status', 'pending')->count(),
            'draft_posts' => Post::where('user_id', $userId)->where('status', 'draft')->count(),
            'rejected_posts' => Post::where('user_id', $userId)->where('status', 'rejected')->count(),
            'total_views' => Post::where('user_id', $userId)->sum('views_count'),
            'total_comments' => Comment::whereHas('post', fn ($q) => $q->where('user_id', $userId))->count(),
        ];

        $recentPosts = Post::where('user_id', $userId)
            ->with('category')
            ->latest()
            ->take(5)
            ->get();

        return view('author.dashboard', compact('stats', 'recentPosts'));
    }
}
