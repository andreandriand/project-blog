@extends('layouts.blog')

@section('title', 'Blog - ' . config('app.name'))

@section('content')
    {{-- Page Header --}}
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Blog</h1>
            <p class="mt-2 text-gray-500 dark:text-gray-400">Jelajahi semua artikel kami</p>

            {{-- Search & Filter --}}
            <form action="{{ route('posts.index') }}" method="GET" class="mt-6 flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari artikel..." class="w-full pl-10 pr-4 py-2.5 input-field">
                </div>
                <select name="category" class="input-field py-2.5">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->slug }}" {{ request('category') == $category->slug ? 'selected' : '' }}>
                            {{ $category->name }} ({{ $category->published_posts_count }})
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Cari
                </button>
                @if(request()->hasAny(['search', 'category', 'tag']))
                    <a href="{{ route('posts.index') }}" class="btn-secondary">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            {{-- Posts Grid --}}
            <div class="flex-1">
                @if($posts->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($posts as $post)
                            <article class="card group">
                                <a href="{{ route('posts.show', $post->slug) }}">
                                    <div class="relative overflow-hidden aspect-video">
                                        <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                        @if($post->is_featured)
                                            <div class="absolute top-3 left-3">
                                                <span class="badge bg-yellow-400 text-yellow-900">Featured</span>
                                            </div>
                                        @endif
                                    </div>
                                </a>
                                <div class="p-5">
                                    <div class="flex items-center gap-2 mb-2">
                                        @if($post->category)
                                            <a href="{{ route('posts.category', $post->category->slug) }}" class="badge badge-primary">{{ $post->category->name }}</a>
                                        @endif
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $post->reading_time }} min</span>
                                    </div>
                                    <a href="{{ route('posts.show', $post->slug) }}">
                                        <h2 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-2">
                                            {{ $post->title }}
                                        </h2>
                                    </a>
                                    @if($post->excerpt)
                                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ $post->excerpt }}</p>
                                    @endif
                                    <div class="mt-4 flex items-center text-sm text-gray-500 dark:text-gray-400">
                                        <img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->name }}" class="w-6 h-6 rounded-full mr-2">
                                        <span>{{ $post->user->name }}</span>
                                        <span class="mx-2">&middot;</span>
                                        <span>{{ $post->published_at->format('d M Y') }}</span>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-8">
                        {{ $posts->links() }}
                    </div>
                @else
                    <div class="text-center py-16">
                        <svg class="w-20 h-20 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <h3 class="text-xl font-semibold text-gray-500 dark:text-gray-400">Tidak ada artikel ditemukan</h3>
                        <p class="text-gray-400 dark:text-gray-500 mt-2">Coba ubah kata kunci pencarian Anda</p>
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="lg:w-72 space-y-6">
                {{-- Tags --}}
                @if($tags->count() > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                        <h3 class="font-bold text-gray-900 dark:text-white mb-4">Tag Populer</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($tags as $tag)
                                <a href="{{ route('posts.tag', $tag->slug) }}" class="px-3 py-1.5 text-sm bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                                    #{{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </div>
@endsection
