@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Artikel</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_posts']) }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">{{ $stats['published_posts'] }} dipublikasi</p>
                </div>
                <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Komentar</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_comments']) }}</p>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">{{ $stats['pending_comments'] }} menunggu</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Views</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_views']) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_users']) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Posts --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Artikel Terbaru</h2>
                <a href="{{ route('admin.posts.index') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">Lihat Semua</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentPosts as $post)
                    <div class="p-4 flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('admin.posts.edit', $post) }}" class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400 truncate block">{{ $post->title }}</a>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $post->user->name }} &middot; {{ $post->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="badge {{ $post->status === 'published' ? 'badge-success' : 'badge-warning' }} ml-3">{{ ucfirst($post->status) }}</span>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500 dark:text-gray-400 text-sm">Belum ada artikel</div>
                @endforelse
            </div>
        </div>

        {{-- Recent Comments --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Komentar Terbaru</h2>
                <a href="{{ route('admin.comments.index') }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">Lihat Semua</a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentComments as $comment)
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $comment->author_display_name }}</span>
                            <span class="badge {{ $comment->is_approved ? 'badge-success' : 'badge-warning' }}">{{ $comment->is_approved ? 'Disetujui' : 'Pending' }}</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">{{ $comment->body }}</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            di <a href="{{ route('admin.posts.edit', $comment->post) }}" class="text-primary-600 dark:text-primary-400 hover:underline">{{ Str::limit($comment->post->title, 30) }}</a>
                            &middot; {{ $comment->created_at->diffForHumans() }}
                        </p>
                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500 dark:text-gray-400 text-sm">Belum ada komentar</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aksi Cepat</h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.posts.create') }}" class="btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tulis Artikel Baru
            </a>
            <a href="{{ route('admin.categories.create') }}" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Kategori
            </a>
            <a href="{{ route('admin.tags.create') }}" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Tag
            </a>
            <a href="{{ route('admin.comments.index', ['status' => 'pending']) }}" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Review Komentar
            </a>
        </div>
    </div>
@endsection
