@extends('layouts.admin')
@section('title', __('Media Manager'))
@section('page-title', __('Media Manager'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
        <form action="{{ route('admin.media.index') }}" method="GET" class="flex flex-1 gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Cari file...') }}" class="input-field flex-1">
        </form>
        <div x-data="{ uploading: false, progress: 0 }">
            <form action="{{ route('admin.media.store') }}" method="POST" enctype="multipart/form-data"
                  x-on:submit="uploading = true"
                  class="flex items-center gap-2">
                @csrf
                <label class="btn-primary cursor-pointer whitespace-nowrap">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    {{ __('Upload Gambar') }}
                    <input type="file" name="files[]" multiple accept="image/*" class="hidden" onchange="this.form.submit()">
                </label>
            </form>
        </div>
    </div>

    @if($media->count() > 0)
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach($media as $item)
                <div x-data="{ showDetail: false }" class="group relative">
                    <div @click="showDetail = true" class="cursor-pointer bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 overflow-hidden hover:border-primary-300 dark:hover:border-primary-600 transition-colors">
                        <div class="aspect-square">
                            <img src="{{ $item->url }}" alt="{{ $item->alt_text ?? $item->original_name }}" class="w-full h-full object-cover">
                        </div>
                        <div class="p-2">
                            <p class="text-xs text-gray-700 dark:text-gray-300 truncate">{{ $item->original_name }}</p>
                            <p class="text-xs text-gray-400">{{ $item->size_formatted }}</p>
                        </div>
                    </div>

                    {{-- Detail Modal --}}
                    <div x-show="showDetail" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showDetail = false">
                        <div x-show="showDetail" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="showDetail = false" class="fixed inset-0 bg-black/60"></div>
                        <div x-show="showDetail" x-transition class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl max-w-2xl w-full z-10 overflow-hidden">
                            <div class="flex flex-col md:flex-row">
                                <div class="md:w-1/2 bg-gray-100 dark:bg-gray-900 flex items-center justify-center p-4">
                                    <img src="{{ $item->url }}" alt="" class="max-h-64 object-contain rounded">
                                </div>
                                <div class="md:w-1/2 p-6">
                                    <h3 class="font-semibold text-gray-900 dark:text-white mb-3 truncate">{{ $item->original_name }}</h3>
                                    <dl class="space-y-2 text-sm">
                                        <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Tipe') }}</dt><dd class="text-gray-900 dark:text-white">{{ $item->mime_type }}</dd></div>
                                        <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Ukuran') }}</dt><dd class="text-gray-900 dark:text-white">{{ $item->size_formatted }}</dd></div>
                                        @if($item->dimensions)<div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Dimensi') }}</dt><dd class="text-gray-900 dark:text-white">{{ $item->dimensions }}</dd></div>@endif
                                        <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Diupload oleh') }}</dt><dd class="text-gray-900 dark:text-white">{{ $item->user->name }}</dd></div>
                                        <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">{{ __('Tanggal') }}</dt><dd class="text-gray-900 dark:text-white">{{ $item->created_at->format('d M Y H:i') }}</dd></div>
                                    </dl>
                                    <div class="mt-4 space-y-2">
                                        <div>
                                            <label class="text-xs text-gray-500 dark:text-gray-400">URL</label>
                                            <div class="flex gap-1">
                                                <input type="text" value="{{ $item->url }}" readonly class="input-field text-xs flex-1" id="url-{{ $item->id }}">
                                                <button onclick="navigator.clipboard.writeText(document.getElementById('url-{{ $item->id }}').value)" class="btn-secondary text-xs px-2">{{ __('Copy') }}</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex gap-2">
                                        <form action="{{ route('admin.media.destroy', $item) }}" method="POST" onsubmit="return confirm('{{ __('Yakin ingin menghapus?') }}')">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                {{ __('Hapus') }}
                                            </button>
                                        </form>
                                        <button @click="showDetail = false" class="btn-secondary text-xs">{{ __('Tutup') }}</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($media->hasPages())
            <div class="mt-6">{{ $media->links() }}</div>
        @endif
    @else
        <div class="text-center py-16">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">{{ __('Belum ada media') }}</h3>
            <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">{{ __('Upload gambar pertama Anda') }}</p>
        </div>
    @endif
@endsection
