@extends('layouts.admin')
@section('title', 'Kelola Komentar')
@section('page-title', 'Komentar')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-2">
            <a href="{{ route('admin.comments.index') }}" class="px-3 py-1.5 text-sm rounded-lg {{ !request('status') ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">Semua</a>
            <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="px-3 py-1.5 text-sm rounded-lg {{ request('status') == 'pending' ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">Pending</a>
            <a href="{{ route('admin.comments.index', ['status' => 'approved']) }}" class="px-3 py-1.5 text-sm rounded-lg {{ request('status') == 'approved' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }} transition-colors">Disetujui</a>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="divide-y divide-gray-100 dark:divide-gray-700">
            @forelse($comments as $comment)
                <div class="p-4 sm:p-6 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-gray-900 dark:text-white text-sm">{{ $comment->author_display_name }}</span>
                                <span class="badge {{ $comment->is_approved ? 'badge-success' : 'badge-warning' }}">{{ $comment->is_approved ? 'Disetujui' : 'Pending' }}</span>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">{{ $comment->body }}</p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                di <a href="{{ route('posts.show', $comment->post->slug) }}" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">{{ Str::limit($comment->post->title, 40) }}</a>
                                &middot; {{ $comment->created_at->diffForHumans() }}
                                @if($comment->author_email) &middot; {{ $comment->author_email }} @endif
                            </p>
                        </div>
                        <div class="flex items-center space-x-1">
                            @if(!$comment->is_approved)
                                <form action="{{ route('admin.comments.approve', $comment) }}" method="POST">@csrf @method('PATCH')
                                    <button class="p-1.5 text-gray-400 hover:text-green-600 transition-colors" title="Setujui"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                                </form>
                            @else
                                <form action="{{ route('admin.comments.reject', $comment) }}" method="POST">@csrf @method('PATCH')
                                    <button class="p-1.5 text-gray-400 hover:text-yellow-600 transition-colors" title="Tolak"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                                </form>
                            @endif
                            <form action="{{ route('admin.comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus komentar ini?')">@csrf @method('DELETE')
                                <button class="p-1.5 text-gray-400 hover:text-red-600 transition-colors" title="Hapus"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-gray-500 dark:text-gray-400">Belum ada komentar</div>
            @endforelse
        </div>
        @if($comments->hasPages()) <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $comments->links() }}</div> @endif
    </div>
@endsection
