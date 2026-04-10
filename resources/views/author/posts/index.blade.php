@extends('layouts.author')
@section('title', 'Artikel Saya')
@section('page-title', 'Artikel Saya')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <form action="{{ route('author.posts.index') }}" method="GET" class="flex flex-1 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari artikel..." class="input-field flex-1">
            <select name="status" class="input-field" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending Review</option>
                <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </form>
        <a href="{{ route('author.posts.create') }}" class="btn-primary whitespace-nowrap">
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
                            </td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ number_format($post->views_count) }}</td>
                            <td class="px-6 py-4 text-gray-500 dark:text-gray-400 text-xs">{{ $post->created_at->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($post->status === 'published')
                                        <a href="{{ route('posts.show', $post->slug) }}" target="_blank" class="p-1.5 text-gray-400 hover:text-blue-600 transition-colors" title="Lihat">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>
                                    @endif
                                    @if(in_array($post->status, ['draft', 'rejected']))
                                        <form action="{{ route('author.posts.submit', $post) }}" method="POST" onsubmit="return confirm('Kirim artikel ini untuk review?')">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="p-1.5 text-gray-400 hover:text-emerald-600 transition-colors" title="Submit untuk Review">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ route('author.posts.edit', $post) }}" class="p-1.5 text-gray-400 hover:text-emerald-600 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <form action="{{ route('author.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus artikel ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors" title="Hapus">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @if($post->status === 'rejected' && $post->rejection_reason)
                            <tr class="bg-red-50 dark:bg-red-900/10">
                                <td colspan="6" class="px-6 py-3">
                                    <div class="flex items-start text-sm text-red-700 dark:text-red-300">
                                        <svg class="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span><strong>Alasan penolakan:</strong> {{ $post->rejection_reason }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">Belum ada artikel. <a href="{{ route('author.posts.create') }}" class="text-emerald-600 dark:text-emerald-400 hover:underline">Tulis artikel pertama Anda!</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($posts->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">{{ $posts->links() }}</div>
        @endif
    </div>
@endsection
