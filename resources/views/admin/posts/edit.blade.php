@extends('layouts.admin')
@section('title', 'Edit Artikel')
@section('page-title', 'Edit Artikel')

@section('content')
    <form action="{{ route('admin.posts.update', $post) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Artikel *</label>
                        <input type="text" name="title" value="{{ old('title', $post->title) }}" required class="w-full input-field text-lg">
                        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ringkasan</label>
                        <textarea name="excerpt" rows="2" class="w-full input-field">{{ old('excerpt', $post->excerpt) }}</textarea>
                        @error('excerpt') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konten Artikel *</label>
                        <textarea name="body" id="editor" rows="15" required class="w-full input-field">{{ old('body', $post->body) }}</textarea>
                        @error('body') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400 mt-1">Mendukung format HTML.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Pengaturan Publikasi</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                        <select name="status" class="w-full input-field">
                            <option value="draft" {{ old('status', $post->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status', $post->status) == 'published' ? 'selected' : '' }}>Published</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $post->is_featured) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Artikel Unggulan</span>
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary flex-1">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Update
                        </button>
                        <a href="{{ route('admin.posts.index') }}" class="btn-secondary">Batal</a>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Kategori</h3>
                    <select name="category_id" class="w-full input-field">
                        <option value="">Pilih Kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $post->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Tag</h3>
                    <div class="space-y-2 max-h-48 overflow-y-auto">
                        @foreach($tags as $tag)
                            <label class="flex items-center">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', $post->tags->pluck('id')->toArray())) ? 'checked' : '' }} class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $tag->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Gambar Utama</h3>
                    @if($post->featured_image)
                        <img src="{{ $post->featured_image_url }}" alt="" class="w-full h-32 object-cover rounded-lg mb-3">
                    @endif
                    <input type="file" name="featured_image" accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-900/30 dark:file:text-primary-300 hover:file:bg-primary-100">
                    @error('featured_image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    </form>
@endsection
