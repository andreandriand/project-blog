<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::published()->with(['user', 'category', 'tags'])->latest('published_at');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag));
        }

        $posts = $query->paginate(9)->withQueryString();
        $categories = Category::withCount('publishedPosts')->orderBy('name')->get();
        $tags = Tag::withCount('posts')->orderByDesc('posts_count')->take(20)->get();

        return view('posts.index', compact('posts', 'categories', 'tags'));
    }

    public function show(Post $post)
    {
        if ($post->status !== 'published') {
            abort(404);
        }

        $sessionKey = 'viewed_post_'.$post->id;
        if (! session()->has($sessionKey)) {
            $post->increment('views_count');
            session()->put($sessionKey, true);
        }

        $post->load(['user', 'category', 'tags', 'approvedComments.replies', 'approvedComments.user']);

        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->where('category_id', $post->category_id)
            ->with(['user', 'category'])
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('posts.show', compact('post', 'relatedPosts'));
    }

    public function byCategory(Category $category)
    {
        $posts = $category->publishedPosts()
            ->with(['user', 'category', 'tags'])
            ->latest('published_at')
            ->paginate(9);

        return view('posts.by-category', compact('category', 'posts'));
    }

    public function byTag(Tag $tag)
    {
        $posts = $tag->posts()
            ->published()
            ->with(['user', 'category', 'tags'])
            ->latest('published_at')
            ->paginate(9);

        return view('posts.by-tag', compact('tag', 'posts'));
    }
}
