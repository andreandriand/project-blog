@php
    $code = '419';
    $title = __('Sesi Kedaluwarsa');
    $description = __('Sesi Anda sudah kedaluwarsa karena terlalu lama tidak aktif. Silakan refresh halaman dan coba lagi.');
@endphp

@include('errors.layout', compact('code', 'title', 'description'))
