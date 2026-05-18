@extends('layouts.admin')
@section('title', 'Generate Artikel dengan AI')
@section('page-title', 'Generate Artikel dengan AI')

@section('content')
    @if(!isset($generated))
        {{-- Step 1: Input Topic --}}
        <div class="max-w-2xl mx-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-lg flex items-center justify-center mr-3">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">AI Article Generator</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Powered by Google Gemini</p>
                    </div>
                </div>

                <form action="{{ route('admin.ai-posts.generate') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Topik Artikel *</label>
                        <textarea name="topic" rows="3" required class="w-full input-field" placeholder="Contoh: Panduan lengkap belajar Laravel untuk pemula, termasuk instalasi, routing, dan Eloquent ORM">{{ old('topic') }}</textarea>
                        @error('topic') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400 mt-1">Semakin detail topik yang diberikan, semakin baik hasil artikel yang dihasilkan.</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bahasa</label>
                        <select name="language" class="w-full input-field">
                            <option value="id" {{ old('language') == 'id' ? 'selected' : '' }}>Bahasa Indonesia</option>
                            <option value="en" {{ old('language') == 'en' ? 'selected' : '' }}>English</option>
                        </select>
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary flex-1 justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Generate Artikel
                        </button>
                        <a href="{{ route('admin.posts.index') }}" class="btn-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    @else
        {{-- Step 2: Preview & Save --}}
        <form action="{{ route('admin.ai-posts.store') }}" method="POST" enctype="multipart/form-data" @submit="$el.setAttribute('data-submitting', '')">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Main Content --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Regenerate Bar --}}
                    <div class="bg-violet-50 dark:bg-violet-900/20 rounded-2xl p-4 border border-violet-200 dark:border-violet-800 flex items-center justify-between">
                        <div class="flex items-center text-sm text-violet-700 dark:text-violet-300">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            Dihasilkan oleh AI dari topik: <strong class="ml-1">{{ $topic }}</strong>
                        </div>
                        <a href="{{ route('admin.ai-posts.create') }}" class="text-sm text-violet-600 dark:text-violet-400 hover:underline font-medium">Generate Ulang</a>
                    </div>

                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Artikel *</label>
                            <input type="text" name="title" value="{{ old('title', $generated['title']) }}" required class="w-full input-field text-lg">
                            @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ringkasan</label>
                            <textarea name="excerpt" rows="2" class="w-full input-field">{{ old('excerpt', $generated['excerpt']) }}</textarea>
                            @error('excerpt') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Konten Artikel *</label>
                            <textarea name="body" id="editor" rows="20" required class="w-full input-field">{{ old('body', $generated['body']) }}</textarea>
                            @error('body') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            <p class="text-xs text-gray-400 mt-1">Anda bisa mengedit konten yang dihasilkan AI sebelum menyimpan.</p>
                        </div>
                    </div>

                    {{-- Preview --}}
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Preview Konten</h3>
                        <div class="prose dark:prose-invert max-w-none">
                            {!! $generated['body'] !!}
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

                        <div class="flex flex-col gap-2">
                            <button type="submit" class="btn-primary w-full justify-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                Simpan Artikel
                            </button>
                            <a href="{{ route('admin.ai-posts.create') }}" class="btn-secondary w-full justify-center text-center">Generate Ulang</a>
                            <a href="{{ route('admin.posts.index') }}" class="text-sm text-center text-gray-500 dark:text-gray-400 hover:underline mt-1">Batal</a>
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
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Gambar Utama') }}</h3>
                        <x-media-picker name="featured_image_path" :apiUrl="route('admin.media.json')" :uploadUrl="route('admin.media.store')" />
                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs text-gray-400 mb-2">{{ __('Atau upload langsung:') }}</p>
                            <input type="file" name="featured_image" accept="image/*" class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 dark:file:bg-primary-900/30 dark:file:text-primary-300 hover:file:bg-primary-100">
                        </div>
                        @error('featured_image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @error('featured_image_path') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </form>
    @endif
@endsection
