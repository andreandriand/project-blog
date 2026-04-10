@extends('layouts.blog')

@section('title', 'Tentang Kami - ' . config('app.name'))

@section('content')
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-900 dark:text-white">Tentang Kami</h1>
            <p class="mt-4 text-xl text-gray-500 dark:text-gray-400">Mengenal lebih dekat {{ config('app.name') }}</p>
        </div>

        <div class="prose max-w-none">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 border border-gray-100 dark:border-gray-700 mb-8">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Misi Kami</h2>
                <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                    {{ config('app.name') }} adalah platform blog modern yang didedikasikan untuk berbagi pengetahuan, inspirasi, dan cerita menarik. Kami percaya bahwa setiap orang memiliki cerita yang layak untuk dibagikan dan pengetahuan yang berharga untuk disebarkan.
                </p>
                <p class="text-gray-600 dark:text-gray-300 leading-relaxed mt-4">
                    Dibangun dengan teknologi terkini seperti Laravel dan Tailwind CSS, kami berkomitmen untuk memberikan pengalaman membaca yang nyaman dan menyenangkan bagi setiap pengunjung.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 text-center">
                    <div class="w-14 h-14 bg-primary-100 dark:bg-primary-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">Konten Berkualitas</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Artikel yang ditulis dengan riset mendalam dan penuh dedikasi</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 text-center">
                    <div class="w-14 h-14 bg-green-100 dark:bg-green-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">Komunitas</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Bergabung dengan komunitas pembaca dan penulis yang aktif</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 text-center">
                    <div class="w-14 h-14 bg-purple-100 dark:bg-purple-900/30 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <h3 class="font-bold text-gray-900 dark:text-white mb-2">Teknologi Modern</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Dibangun dengan Laravel 13 dan teknologi web terkini</p>
                </div>
            </div>
        </div>
    </div>
@endsection
