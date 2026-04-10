<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;

class HomeController extends Controller
{
    public function index()
    {
        $featuredPosts = Post::published()
            ->featured()
            ->with(['user', 'category'])
            ->latest('published_at')
            ->take(3)
            ->get();

        $latestPosts = Post::published()
            ->with(['user', 'category', 'tags'])
            ->latest('published_at')
            ->take(6)
            ->get();

        $categories = Category::withCount('publishedPosts')
            ->get()
            ->filter(fn($cat) => $cat->published_posts_count > 0)
            ->sortByDesc('published_posts_count')
            ->take(8);

        return view('home', compact('featuredPosts', 'latestPosts', 'categories'));
    }
}
