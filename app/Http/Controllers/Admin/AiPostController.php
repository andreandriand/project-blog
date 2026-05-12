<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\GeminiService;
use App\Traits\GeneratesUniqueSlug;
use Illuminate\Http\Request;
use Mews\Purifier\Facades\Purifier;

/**
 * Tujuan: Admin-only AI post generator via Google Gemini, lalu simpan sebagai Post.
 * Caller: routes/web.php grup admin -> admin.ai-posts.* -> Admin\AiPostController.
 * Dependensi: App\Services\GeminiService, GeneratesUniqueSlug trait, Models Post/Category/Tag, Mews\Purifier.
 * Main Functions: create, generate, store.
 * Side Effects: HTTP call ke Gemini API (generate), DB write posts & post_tag (store), sanitasi body via Purifier.
 *
 * Catatan keamanan: output Gemini adalah HTML dari LLM (untrusted). WAJIB disanitasi via Purifier preset 'blog'
 * sebelum disimpan ke DB untuk mencegah Stored XSS — LLM bisa saja dipengaruhi prompt injection.
 */
class AiPostController extends Controller
{
    use GeneratesUniqueSlug;

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

        $validated['body'] = Purifier::clean($validated['body'], 'blog');
        $validated['user_id'] = auth()->id();
        $validated['slug'] = $this->generateUniqueSlug($validated['title'], Post::class);

        if ($validated['status'] === 'published') {
            $validated['published_at'] = now();
        }

        $validated['is_featured'] = $request->boolean('is_featured');

        $post = Post::create($validated);

        if ($request->has('tags')) {
            $post->tags()->sync($request->tags);
        }

        return redirect()->route('admin.posts.index')
            ->with('success', __('Artikel AI berhasil disimpan!'));
    }
}
