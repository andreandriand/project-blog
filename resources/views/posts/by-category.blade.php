@extends('layouts.blog')

@section('title', 'Kategori: ' . $category->name . ' - ' . config('app.name'))

@section('content')
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-4 space-x-2">
                <a href="{{ route('home') }}" class="hover:text-primary-600">{{ __('Beranda') }}</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span>{{ __('Kategori') }}</span>
            </nav>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $category->name }}</h1>
            @if($category->description)
                <p class="mt-2 text-gray-500 dark:text-gray-400">{{ $category->description }}</p>
            @endif
            <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">{{ __(':count artikel', ['count' => $posts->total()]) }}</p>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if($posts->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($posts as $post)
                    <article class="card group">
                        <a href="{{ route('posts.show', $post->slug) }}">
                            <div class="relative overflow-hidden aspect-video">
                                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                        </a>
                        <div class="p-5">
                            <a href="{{ route('posts.show', $post->slug) }}">
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-2">{{ $post->title }}</h2>
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
            <div class="mt-8">{{ $posts->links() }}</div>
        @else
            <div class="text-center py-16">
                <p class="text-gray-500 dark:text-gray-400">{{ __('Belum ada artikel dalam kategori ini.') }}</p>
            </div>
        @endif
    </div>
@endsection
