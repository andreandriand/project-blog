<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::with('user')->latest();

        if ($request->filled('search')) {
            $query->where('original_name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $media = $query->paginate(24)->withQueryString();

        return view('admin.media.index', compact('media'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'files' => 'required|array|max:10',
            'files.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
        ]);

        $uploaded = [];

        foreach ($request->file('files') as $file) {
            $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('media', $filename, 'public');

            $dimensions = @getimagesize($file->getRealPath());

            $uploaded[] = Media::create([
                'user_id' => auth()->id(),
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => $dimensions[0] ?? null,
                'height' => $dimensions[1] ?? null,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => __(':count file berhasil diupload.', ['count' => count($uploaded)]),
                'media' => $uploaded,
            ]);
        }

        return back()->with('success', __(':count file berhasil diupload.', ['count' => count($uploaded)]));
    }

    public function destroy(Media $medium)
    {
        Storage::disk('public')->delete($medium->path);
        $medium->delete();

        if (request()->wantsJson()) {
            return response()->json(['message' => __('Media berhasil dihapus!')]);
        }

        return back()->with('success', __('Media berhasil dihapus!'));
    }

    public function json(Request $request)
    {
        $query = Media::latest();

        if ($request->filled('search')) {
            $query->where('original_name', 'like', '%'.$request->search.'%');
        }

        $media = $query->paginate(24);

        return response()->json($media);
    }
}
