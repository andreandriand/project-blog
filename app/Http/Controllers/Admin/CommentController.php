<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $query = Comment::with(['post', 'user'])->latest();

        if ($request->filled('status')) {
            if ($request->status === 'pending') {
                $query->where('is_approved', false);
            } elseif ($request->status === 'approved') {
                $query->where('is_approved', true);
            }
        }

        $comments = $query->paginate(15)->withQueryString();

        return view('admin.comments.index', compact('comments'));
    }

    public function approve(Comment $comment)
    {
        $comment->update(['is_approved' => true]);

        return back()->with('success', __('Komentar berhasil disetujui!'));
    }

    public function reject(Comment $comment)
    {
        $comment->update(['is_approved' => false]);

        return back()->with('success', __('Komentar berhasil ditolak!'));
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();

        return back()->with('success', __('Komentar berhasil dihapus!'));
    }
}
