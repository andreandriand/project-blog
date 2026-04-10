@extends('layouts.admin')
@section('title', 'Kelola Artikel')
@section('page-title', 'Artikel')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <form action="{{ route('admin.posts.index') }}" method="GET" class="flex flex-1 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari artikel..." class="input-field flex-1">
            <select name="status" class="input-field" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending Review</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </form>
        <a href="{{ route('admin.posts.create') }}" class="btn-primary whitespace-nowrap">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tulis Artikel
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Artikel</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Kategori</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Views</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($posts as $post)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <img src="{{ $post->featured_image_url }}" alt="" class="w-12 h-8 rounded object-cover mr-3">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ Str::limit($post->title, 50) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $post->user->name }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $post->category->name ?? '-' }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $badgeClass = match($post->status) {
                                        'published' => 'badge-success',
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        default => 'badge-warning',
                                    };
                                    $statusLabel = match($post->status) {
                                        'pending' => 'Pending Review',
                                        default => ucfirst($post->status),
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                @if($post->is_featured) <span class="badge bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 ml-1">Featured</span> @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ number_format($post->views_count) }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-xs">{{ $post->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($post->status === 'pending')
                                        <form action="{{ route('admin.posts.approve', $post) }}" method="POST" onsubmit="return confirm('Approve artikel ini?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="p-1.5 text-gray-400 hover:text-green-600 transition-colors" title="Approve">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        </form>
                                        <button type="button" x-data @click="$dispatch('open-reject-modal', { postId: {{ $post->id }}, postTitle: '{{ addslashes($post->title) }}' })" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors" title="Reject">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                    @endif
                                    <a href="{{ route('posts.show', $post->slug) }}" target="_blank" class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors" title="Lihat">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                    <a href="{{ route('admin.posts.edit', $post) }}" class="p-1.5 text-gray-400 hover:text-primary-600 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus artikel ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">Belum ada artikel</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($posts->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $posts->links() }}</div>
        @endif
    </div>

    {{-- Reject Modal --}}
    <div x-data="{ open: false, postId: null, postTitle: '' }" @open-reject-modal.window="open = true; postId = $event.detail.postId; postTitle = $event.detail.postTitle" x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="open = false" class="fixed inset-0 bg-black/50"></div>

            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-md w-full p-6 z-10">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">Tolak Artikel</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" x-text="postTitle"></p>

                <form :action="'/admin/posts/' + postId + '/reject'" method="POST">
                    @csrf @method('PATCH')
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alasan Penolakan *</label>
                        <textarea name="rejection_reason" rows="4" required class="w-full input-field" placeholder="Jelaskan alasan penolakan artikel ini..."></textarea>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button type="button" @click="open = false" class="btn-secondary">Batal</button>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Tolak Artikel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
