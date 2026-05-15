@php
    $code = '404';
    $title = __('Halaman Tidak Ditemukan');
    $description = __('URL yang Anda cari tidak ada atau sudah dipindahkan. Coba cek alamatnya, atau lanjutkan menjelajah dari beranda.');
@endphp

@include('errors.layout', compact('code', 'title', 'description'))
