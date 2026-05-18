@extends('layouts.author')
@section('title', 'Tulis Artikel Baru')
@section('page-title', 'Tulis Artikel Baru')

@section('content')
    <form action="{{ route('author.posts.store') }}" method="POST" enctype="multipart/form-data" @submit="$el.setAttribute('data-submitting', '')">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Artikel *</label>
                        <input type="text" name="title" value="{{ old('title') }}" required class="w-full input-field text-lg" placeholder="Masukkan judul artikel...">
                        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ringkasan</label>
                        <textarea name="excerpt" rows="2" class="w-full input-field" placeholder="Ringkasan singkat artikel...">{{ old('excerpt') }}</textarea>
                        @error('excerpt') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konten Artikel *</label>
                        <textarea name="body" id="editor" rows="15" required class="w-full input-field" placeholder="Tulis konten artikel Anda di sini... (mendukung HTML)">{{ old('body') }}</textarea>
                        @error('body') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400 mt-1">Mendukung format HTML. Gunakan tag &lt;h2&gt;, &lt;h3&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;blockquote&gt;, &lt;code&gt;, dll.</p>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Publish Settings --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Publikasi</h3>
                    <div class="flex flex-col gap-2">
                        <button type="submit" class="btn-primary w-full justify-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            Simpan Draft
                        </button>
                        <button type="submit" name="submit_review" value="1" class="w-full inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Submit untuk Review
                        </button>
                        <a href="{{ route('author.posts.index') }}" class="btn-secondary w-full justify-center text-center">Batal</a>
                    </div>
                </div>

                {{-- Category --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Kategori</h3>
                    <select name="category_id" class="w-full input-field">
                        <option value="">Pilih Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tags --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Tag</h3>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @foreach($tags as $tag)
                            <label class="flex items-center">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-emerald-600 focus:ring-emerald-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Featured Image --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Gambar Utama') }}</h3>
                    <x-media-picker name="featured_image_path" :apiUrl="route('author.media.json')" :uploadUrl="route('author.media.store')" />
                    <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs text-gray-400 mb-2">{{ __('Atau upload langsung:') }}</p>
                        <input type="file" name="featured_image" accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 dark:file:bg-emerald-900/30 dark:file:text-emerald-300 hover:file:bg-emerald-100">
                    </div>
                    @error('featured_image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </form>
@endsection
