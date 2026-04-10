<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiPostController extends Controller
{
    public function create()
    {
        return view('admin.posts.ai-generate');
    }

    public function generate(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'topic' => 'required|max:500',
            'language' => 'required|in:id,en',
        ]);

        try {
            $result = $gemini->generatePost($request->topic, $request->language);
        } catch (\RuntimeException $e) {
            return back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.posts.ai-generate', [
            'generated' => $result,
            'topic' => $request->topic,
            'language' => $request->language,
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'excerpt' => 'nullable|max:500',
            'body' => 'required',
            'status' => 'required|in:draft,published',
            'is_featured' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['slug'] = Str::slug($validated['title']);

        $count = Post::where('slug', $validated['slug'])->count();
        if ($count > 0) {
            $validated['slug'] .= '-'.($count + 1);
        }

        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $validated['is_featured'] = $request->boolean('is_featured');

        $post = Post::create($validated);

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        return redirect()->route('admin.posts.index')
            ->with('success', 'Artikel AI berhasil disimpan!');
    }
}
