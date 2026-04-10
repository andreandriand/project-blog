<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $posts = Post::published()
            ->select('slug', 'updated_at', 'featured_image')
            ->latest('updated_at')
            ->get();

        $categories = Category::select('slug', 'updated_at')->get();
        $tags = Tag::select('slug', 'updated_at')->get();

        $content = view('sitemap', compact('posts', 'categories', 'tags'))->render();

        return response($content, 200)
            ->header('Content-Type', 'application/xml');
    }
}
