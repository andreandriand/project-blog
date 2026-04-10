@extends('layouts.admin')
@section('title', 'Tulis Artikel Baru')
@section('page-title', 'Tulis Artikel Baru')

@section('content')
    <form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data">
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
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Pengaturan Publikasi</h3>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select name="status" class="w-full input-field">
                            <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Published</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Artikel Unggulan</span>
                        </label>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary flex-1">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Simpan
                        </button>
                        <a href="{{ route('admin.posts.index') }}" class="btn-secondary">Batal</a>
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
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Featured Image --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Gambar Utama</h3>
                    <input type="file" name="featured_image" accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-900/30 dark:file:text-primary-300 hover:file:bg-primary-100">
                    @error('featured_image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </form>
@endsection
