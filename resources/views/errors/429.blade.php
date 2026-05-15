@php
    $code = '429';
    $title = __('Terlalu Banyak Permintaan');
    $description = __('Anda mengirim terlalu banyak permintaan dalam waktu singkat. Tunggu sebentar lalu coba lagi.');
@endphp

@include('errors.layout', compact('code', 'title', 'description'))
