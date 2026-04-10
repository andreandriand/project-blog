@extends('layouts.blog')

@section('title', config('app.name') . ' - Blog Modern')

@section('content')
    {{-- Hero Section --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-primary-600 via-purple-600 to-pink-500">
        <div class="absolute inset-0 bg-black/20"></div>
        <div class="absolute inset-0">
            <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse"></div>
            <div class="absolute top-0 -right-4 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse" style="animation-delay: 2s"></div>
            <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-pulse" style="animation-delay: 4s"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
            <div class="text-center">
                <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-6 leading-tight">
                    Temukan Inspirasi<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 to-pink-200">di Setiap Tulisan</span>
                </h1>
                <p class="text-lg md:text-xl text-white/80 max-w-2xl mx-auto mb-8">
                    Jelajahi artikel-artikel menarik tentang teknologi, desain, dan pengembangan diri. Ditulis oleh para ahli untuk menginspirasi perjalanan Anda.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('posts.index') }}" class="inline-flex items-center justify-center px-8 py-3 bg-white text-primary-700 font-semibold rounded-xl hover:bg-gray-100 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                        Mulai Membaca
                    </a>
                    <a href="{{ route('about') }}" class="inline-flex items-center justify-center px-8 py-3 border-2 border-white/30 text-white font-semibold rounded-xl hover:bg-white/10 transition-all">
                        Tentang Kami
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Featured Posts --}}
    @if($featuredPosts->count() > 0)
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-12 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($featuredPosts as $post)
                <a href="{{ route('posts.show', $post->slug) }}" class="card group">
                    <div class="relative overflow-hidden aspect-video">
                        <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-3 left-3">
                            <span class="badge badge-primary">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                Featured
                            </span>
                        </div>
                    </div>
                    <div class="p-5">
                        @if($post->category)
                            <span class="text-xs font-semibold text-primary-600 dark:text-primary-400 uppercase tracking-wider">{{ $post->category->name }}</span>
                        @endif
                        <h3 class="mt-1 text-lg font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-2">
                            {{ $post->title }}
                        </h3>
                        <div class="mt-3 flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->name }}" class="w-6 h-6 rounded-full mr-2">
                            <span>{{ $post->user->name }}</span>
                            <span class="mx-2">&middot;</span>
                            <span>{{ $post->published_at->format('d M Y') }}</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- Latest Posts --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Artikel Terbaru</h2>
                <p class="mt-1 text-gray-500 dark:text-gray-400">Tulisan terbaru dari para penulis kami</p>
            </div>
            <a href="{{ route('posts.index') }}" class="hidden sm:inline-flex items-center text-primary-600 dark:text-primary-400 font-semibold hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                Lihat Semua
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @forelse($latestPosts as $post)
                <article class="card group">
                    <a href="{{ route('posts.show', $post->slug) }}">
                        <div class="relative overflow-hidden aspect-video">
                            <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                    </a>
                    <div class="p-5">
                        <div class="flex items-center gap-2 mb-3">
                            @if($post->category)
                                <a href="{{ route('posts.category', $post->category->slug) }}" class="badge badge-primary">{{ $post->category->name }}</a>
                            @endif
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $post->reading_time }} min baca</span>
                        </div>
                        <a href="{{ route('posts.show', $post->slug) }}">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-2">
                                {{ $post->title }}
                            </h3>
                        </a>
                        @if($post->excerpt)
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 line-clamp-2">{{ $post->excerpt }}</p>
                        @endif
                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->name }}" class="w-8 h-8 rounded-full mr-2">
                                <div>
                                    <p class="font-medium text-gray-700 dark:text-gray-300">{{ $post->user->name }}</p>
                                    <p class="text-xs">{{ $post->published_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center text-xs text-gray-400">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                {{ number_format($post->views_count) }}
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                    <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400">Belum ada artikel</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500">Artikel akan segera hadir!</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Categories Section --}}
    @if($categories->count() > 0)
    <section class="bg-white dark:bg-gray-800 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Jelajahi Kategori</h2>
                <p class="mt-2 text-gray-500 dark:text-gray-400">Temukan artikel berdasarkan topik yang Anda minati</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($categories as $category)
                    <a href="{{ route('posts.category', $category->slug) }}" class="group p-6 bg-gray-50 dark:bg-gray-700/50 rounded-2xl hover:bg-primary-50 dark:hover:bg-primary-900/20 border border-gray-100 dark:border-gray-700 hover:border-primary-200 dark:hover:border-primary-800 transition-all duration-300">
                        <div class="w-12 h-12 bg-primary-100 dark:bg-primary-900/30 rounded-xl flex items-center justify-center mb-3 group-hover:bg-primary-200 dark:group-hover:bg-primary-900/50 transition-colors">
                            <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-700 dark:group-hover:text-primary-300 transition-colors">{{ $category->name }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $category->published_posts_count }} artikel</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    {{-- Newsletter CTA --}}
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="bg-gradient-to-r from-primary-600 to-purple-600 rounded-3xl p-8 md:p-12 text-center relative overflow-hidden">
            <div class="absolute inset-0 bg-grid-white/10"></div>
            <div class="relative">
                <h2 class="text-2xl md:text-3xl font-bold text-white mb-3">Jangan Lewatkan Update Terbaru</h2>
                <p class="text-white/80 max-w-xl mx-auto mb-6">Dapatkan notifikasi setiap ada artikel baru. Gratis dan tanpa spam!</p>
                <div class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
                    <input type="email" placeholder="Masukkan email Anda" class="flex-1 px-4 py-3 rounded-xl border-0 focus:ring-2 focus:ring-white/50 bg-white/20 text-white placeholder-white/60 backdrop-blur-sm">
                    <button class="px-6 py-3 bg-white text-primary-700 font-semibold rounded-xl hover:bg-gray-100 transition-colors shadow-lg">
                        Berlangganan
                    </button>
                </div>
            </div>
        </div>
    </section>
@endsection
