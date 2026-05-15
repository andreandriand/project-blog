@php
    $code = '503';
    $title = __('Sedang Pemeliharaan');
    $description = $exception?->getMessage() ?: __('Situs sedang dalam pemeliharaan terjadwal. Kami akan kembali secepatnya. Terima kasih atas kesabaran Anda.');
@endphp

@include('errors.layout', compact('code', 'title', 'description'))
