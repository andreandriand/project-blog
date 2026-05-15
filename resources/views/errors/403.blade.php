@php
    $code = '403';
    $title = __('Akses Ditolak');
    $description = __('Anda tidak memiliki izin untuk mengakses halaman ini. Hubungi administrator jika ini seharusnya bisa diakses.');
@endphp

@include('errors.layout', compact('code', 'title', 'description'))
