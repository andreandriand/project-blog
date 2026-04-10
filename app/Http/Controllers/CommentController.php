<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Post $post)
    {
        $rules = [
            'body' => 'required|min:3|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ];

        if (!auth()->check()) {
            $rules['author_name'] = 'required|max:255';
            $rules['author_email'] = 'required|email|max:255';
        }

        $validated = $request->validate($rules);

        $comment = $post->comments()->create([
            'user_id' => auth()->id(),
            'author_name' => $validated['author_name'] ?? null,
            'author_email' => $validated['author_email'] ?? null,
            'body' => $validated['body'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_approved' => auth()->check(), // Auto-approve for logged-in users
        ]);

        $message = auth()->check()
            ? 'Komentar berhasil ditambahkan!'
            : 'Komentar berhasil dikirim dan menunggu persetujuan.';

        return back()->with('success', $message);
    }
}
