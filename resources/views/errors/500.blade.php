@php
    $code = '500';
    $title = __('Server Bermasalah');
    $description = __('Terjadi kesalahan internal di server kami. Tim teknis sudah mendapat notifikasi dan sedang mengatasi. Silakan coba beberapa saat lagi.');
@endphp

@include('errors.layout', compact('code', 'title', 'description'))
