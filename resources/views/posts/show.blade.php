@extends('layouts.blog')

@section('title', $post->title . ' - ' . config('app.name'))
@section('meta_description', $post->excerpt ?? Str::limit(strip_tags($post->body), 160))

@section('content')
    <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Breadcrumb --}}
        <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-6 space-x-2">
            <a href="{{ route('home') }}" class="hover:text-primary-600 dark:hover:text-primary-400">{{ __('Beranda') }}</a>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            <a href="{{ route('posts.index') }}" class="hover:text-primary-600 dark:hover:text-primary-400">Blog</a>
            @if($post->category)
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <a href="{{ route('posts.category', $post->category->slug) }}" class="hover:text-primary-600 dark:hover:text-primary-400">{{ $post->category->name }}</a>
            @endif
        </nav>

        {{-- Post Header --}}
        <header class="mb-8">
            <div class="flex items-center gap-3 mb-4">
                @if($post->category)
                    <a href="{{ route('posts.category', $post->category->slug) }}" class="badge badge-primary">{{ $post->category->name }}</a>
                @endif
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $post->reading_time }} {{ __('min baca') }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    {{ number_format($post->views_count) }} views
                </span>
            </div>

            <h1 class="text-3xl md:text-4xl lg:text-5xl font-extrabold text-gray-900 dark:text-white leading-tight">
                {{ $post->title }}
            </h1>

            @if($post->excerpt)
                <p class="mt-4 text-xl text-gray-500 dark:text-gray-400 leading-relaxed">{{ $post->excerpt }}</p>
            @endif

            {{-- Author Info --}}
            <div class="mt-6 flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center">
                    <img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->name }}" class="w-12 h-12 rounded-full mr-4">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-white">{{ $post->user->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $post->published_at->format('d F Y') }} &middot; {{ __('Diperbarui') }} {{ $post->updated_at->diffForHumans() }}
                        </p>
                    </div>
                </div>

                {{-- Share Buttons --}}
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-500 dark:text-gray-400 mr-1">{{ __('Bagikan:') }}</span>
                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($post->title) }}" target="_blank" class="w-9 h-9 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center text-gray-500 hover:bg-blue-100 hover:text-blue-500 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}" target="_blank" class="w-9 h-9 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center text-gray-500 hover:bg-blue-100 hover:text-blue-600 transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.77 7.46H14.5v-1.9c0-.9.6-1.1 1-1.1h3V.5h-4.33C10.24.5 9.5 3.44 9.5 5.32v2.15h-3v4h3v12h5v-12h3.85l.42-4z"/></svg>
                    </a>
                    <button onclick="navigator.clipboard.writeText(window.location.href); this.innerHTML='<svg class=\'w-4 h-4\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'/></svg>'; setTimeout(() => this.innerHTML='<svg class=\'w-4 h-4\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3\'/></svg>', 2000)" class="w-9 h-9 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center text-gray-500 hover:bg-green-100 hover:text-green-600 transition-colors" title="Copy link">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                    </button>
                </div>
            </div>
        </header>

        {{-- Featured Image --}}
        @if($post->featured_image)
            <div class="mb-8 rounded-2xl overflow-hidden">
                <img src="{{ $post->featured_image_url }}" alt="{{ $post->title }}" class="w-full h-auto">
            </div>
        @endif

        {{-- Post Content --}}
        <div class="prose max-w-none">
            {!! $post->body !!}
        </div>

        {{-- Tags --}}
        @if($post->tags->count() > 0)
            <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center flex-wrap gap-2">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Tags:') }}</span>
                    @foreach($post->tags as $tag)
                        <a href="{{ route('posts.tag', $tag->slug) }}" class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg hover:bg-primary-100 dark:hover:bg-primary-900/30 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                            #{{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Author Bio --}}
        <div class="mt-8 p-6 bg-gray-50 dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700">
            <div class="flex items-start space-x-4">
                <img src="{{ $post->user->avatar_url }}" alt="{{ $post->user->name }}" class="w-16 h-16 rounded-full">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white">{{ $post->user->name }}</h3>
                    <p class="text-sm text-primary-600 dark:text-primary-400 mb-2">{{ ucfirst($post->user->role) }}</p>
                    @if($post->user->bio)
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $post->user->bio }}</p>
                    @endif
                </div>
            </div>
        </div>
    </article>

    {{-- Comments Section --}}
    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            {{ __('Komentar') }} ({{ $post->approvedComments->count() }})
        </h2>

        {{-- Comment Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 mb-8">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">{{ __('Tinggalkan Komentar') }}</h3>
            <form action="{{ route('comments.store', $post) }}" method="POST">
                @csrf
                @guest
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Nama') }} *</label>
                            <input type="text" name="author_name" value="{{ old('author_name') }}" required class="w-full input-field">
                            @error('author_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }} *</label>
                            <input type="email" name="author_email" value="{{ old('author_email') }}" required class="w-full input-field">
                            @error('author_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                @endguest
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Komentar *') }}</label>
                    <textarea name="body" rows="4" required class="w-full input-field" placeholder="{{ __('Tulis komentar Anda...') }}">{{ old('body') }}</textarea>
                    @error('body') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    {{ __('Kirim Komentar') }}
                </button>
            </form>
        </div>

        {{-- Comments List --}}
        <div class="space-y-6">
            @forelse($post->approvedComments as $comment)
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-start space-x-3">
                        @if($comment->user)
                            <img src="{{ $comment->user->avatar_url }}" alt="{{ $comment->user->name }}" class="w-10 h-10 rounded-full">
                        @else
                            <div class="w-10 h-10 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                        @endif
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm">{{ $comment->author_display_name }}</h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-2 text-gray-600 dark:text-gray-300 text-sm">{{ $comment->body }}</p>

                            {{-- Replies --}}
                            @if($comment->replies->count() > 0)
                                <div class="mt-4 ml-4 space-y-4 border-l-2 border-gray-200 dark:border-gray-700 pl-4">
                                    @foreach($comment->replies as $reply)
                                        <div class="flex items-start space-x-3">
                                            @if($reply->user)
                                                <img src="{{ $reply->user->avatar_url }}" alt="{{ $reply->user->name }}" class="w-8 h-8 rounded-full">
                                            @else
                                                <div class="w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                </div>
                                            @endif
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h5 class="font-semibold text-gray-900 dark:text-white text-xs">{{ $reply->author_display_name }}</h5>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="mt-1 text-gray-600 dark:text-gray-300 text-sm">{{ $reply->body }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Belum ada komentar. Jadilah yang pertama!') }}</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Related Posts --}}
    @if($relatedPosts->count() > 0)
        <section class="bg-gray-50 dark:bg-gray-800/50 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __('Artikel Terkait') }}</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($relatedPosts as $related)
                        <a href="{{ route('posts.show', $related->slug) }}" class="card group">
                            <div class="relative overflow-hidden aspect-video">
                                <img src="{{ $related->featured_image_url }}" alt="{{ $related->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                            <div class="p-4">
                                <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors line-clamp-2">{{ $related->title }}</h3>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $related->published_at->format('d M Y') }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
