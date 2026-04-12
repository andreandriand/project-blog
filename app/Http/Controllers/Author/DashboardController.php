<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $postStats = Post::where('user_id', $userId)
            ->select([
                DB::raw('COUNT(*) as total_posts'),
                DB::raw("COUNT(CASE WHEN status = 'published' THEN 1 END) as published_posts"),
                DB::raw("COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_posts"),
                DB::raw("COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_posts"),
                DB::raw("COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_posts"),
                DB::raw('COALESCE(SUM(views_count), 0) as total_views'),
            ])
            ->first();

        $stats = array_merge(
            (array) $postStats->getAttributes(),
            ['total_comments' => Comment::whereHas('post', fn ($q) => $q->where('user_id', $userId))->count()]
        );

        $recentPosts = Post::where('user_id', $userId)
            ->with('category')
            ->latest()
            ->take(5)
            ->get();

        return view('author.dashboard', compact('stats', 'recentPosts'));
    }
}
