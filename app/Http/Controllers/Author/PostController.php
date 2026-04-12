<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::where('user_id', auth()->id())
            ->with(['category', 'tags'])
            ->latest();

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $posts = $query->paginate(10)->withQueryString();

        return view('author.posts.index', compact('posts'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('author.posts.create', compact('categories', 'tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'excerpt' => 'nullable|max:500',
            'body' => 'required',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        $validated['user_id'] = auth()->id();
        $validated['slug'] = Str::slug($validated['title']);

        $count = Post::where('slug', $validated['slug'])->count();
        if ($count > 0) {
            $validated['slug'] .= '-'.($count + 1);
        }

        if ($request->filled('featured_image_path')) {
            $validated['featured_image'] = $request->featured_image_path;
        } elseif ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('posts', 'public');
        }

        $validated['status'] = $request->has('submit_review') ? 'pending' : 'draft';

        $post = Post::create($validated);

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        $message = $validated['status'] === 'pending'
            ? __('Artikel berhasil dikirim untuk review!')
            : __('Artikel berhasil disimpan sebagai draft!');

        return redirect()->route('author.posts.index')->with('success', $message);
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);

        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $post->load('tags');

        return view('author.posts.edit', compact('post', 'categories', 'tags'));
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $validated = $request->validate([
            'title' => 'required|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'excerpt' => 'nullable|max:500',
            'body' => 'required',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ]);

        if ($request->filled('featured_image_path')) {
            $validated['featured_image'] = $request->featured_image_path;
        } elseif ($request->hasFile('featured_image')) {
            if ($post->featured_image) {
                Storage::disk('public')->delete($post->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('posts', 'public');
        }

        if (in_array($post->status, ['rejected'])) {
            $validated['status'] = 'draft';
            $validated['rejection_reason'] = null;
        }

        if ($request->has('submit_review') && in_array($post->status, ['draft', 'rejected'])) {
            $validated['status'] = 'pending';
            $validated['rejection_reason'] = null;
        }

        $post->update($validated);
        $post->tags()->sync($request->tags ?? []);

        $message = ($validated['status'] ?? $post->status) === 'pending'
            ? __('Artikel berhasil dikirim untuk review!')
            : __('Artikel berhasil diperbarui!');

        return redirect()->route('author.posts.index')->with('success', $message);
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        if ($post->featured_image) {
            Storage::disk('public')->delete($post->featured_image);
        }

        $post->delete();

        return redirect()->route('author.posts.index')->with('success', __('Artikel berhasil dihapus!'));
    }

    public function submitForReview(Post $post)
    {
        $this->authorize('update', $post);

        if (! in_array($post->status, ['draft', 'rejected'])) {
            return back()->with('error', __('Artikel ini tidak bisa disubmit untuk review.'));
        }

        $post->update([
            'status' => 'pending',
            'rejection_reason' => null,
        ]);

        return back()->with('success', __('Artikel berhasil dikirim untuk review!'));
    }
}
