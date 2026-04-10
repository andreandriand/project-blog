<?php

namespace App\Http\Controllers\Author;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        $query = Comment::whereHas('post', fn ($q) => $q->where('user_id', $userId))
            ->with(['post', 'user'])
            ->latest();

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->status === 'approved') {
                $query->where('is_approved', true);
            }
        }

        $comments = $query->paginate(15)->withQueryString();

        return view('author.comments.index', compact('comments'));
    }

    public function approve(Comment $comment)
    {
        if ($comment->post->user_id !== auth()->id()) {
            abort(403);
        }

        $comment->update(['is_approved' => true]);

        return back()->with('success', 'Komentar berhasil disetujui!');
    }

    public function destroy(Comment $comment)
    {
        if ($comment->post->user_id !== auth()->id()) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Komentar berhasil dihapus!');
    }
}
