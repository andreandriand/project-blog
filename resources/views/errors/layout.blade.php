{{--
Tujuan: Layout shared untuk semua error page (404, 403, 500, 503, 419, 429).
Caller: Di-extend oleh resources/views/errors/{code}.blade.php.
Dependensi: Vite (app.css), Bunny Fonts (Inter), config('app.name').
Main Functions: Slot $code, $title, $message, $description.
Side Effects: Tidak ada — pure render.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">

    <title>{{ $title ?? __('Error') }} &middot; {{ config('app.name', 'AndBlog') }}</title>

    @include('partials.favicon')

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 text-gray-900 dark:text-gray-100">
    <div class="min-h-screen flex flex-col">
        <main class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12">
            <div class="max-w-xl w-full text-center">
                {{-- Brand --}}
                <a href="{{ url('/') }}" class="inline-flex items-center space-x-2 mb-10">
                    <img src="{{ asset('images/logo.webp') }}" alt="{{ config('app.name', 'AndBlog') }}" class="h-10 w-auto">
                    <span class="text-xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
                        {{ config('app.name', 'AndBlog') }}
                    </span>
                </a>

                {{-- Error code (large, decorative) --}}
                <div class="relative mb-2">
                    <h1 class="text-[120px] sm:text-[160px] leading-none font-extrabold bg-gradient-to-br from-primary-500 via-purple-500 to-pink-500 bg-clip-text text-transparent select-none">
                        {{ $code ?? '?' }}
                    </h1>
                </div>

                {{-- Heading --}}
                <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-3">
                    {{ $title ?? __('Terjadi kesalahan') }}
                </h2>

                {{-- Description --}}
                <p class="text-base sm:text-lg text-gray-500 dark:text-gray-400 mb-8 leading-relaxed">
                    {{ $description ?? __('Maaf, sesuatu sedang tidak beres.') }}
                </p>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-primary-600 to-purple-600 text-white font-semibold rounded-xl hover:shadow-lg transform hover:-translate-y-0.5 transition-all">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        {{ __('Kembali ke Beranda') }}
                    </a>
                    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="inline-flex items-center justify-center px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ __('Halaman Sebelumnya') }}
                    </a>
                </div>

                @isset($extra)
                    <div class="mt-10 pt-8 border-t border-gray-200 dark:border-gray-700 text-sm text-gray-500 dark:text-gray-400">
                        {{ $extra }}
                    </div>
                @endisset
            </div>
        </main>

        <footer class="text-center py-6 text-xs text-gray-400 dark:text-gray-600">
            &copy; {{ date('Y') }} {{ config('app.name', 'AndBlog') }}
        </footer>
    </div>
</body>

</html>
