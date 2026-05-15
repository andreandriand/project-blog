{{--
Tujuan: Single source of truth untuk favicon links di semua layout.
Caller: Di-@include oleh layouts/{app,guest,blog,admin,author}.blade.php dan errors/layout.blade.php.
Dependensi: public/favicon.ico (wajib), opsional public/favicon.svg dan public/apple-touch-icon.png.
Main Functions: Render <link rel="icon"> dan variant terkait dengan cache-busting.
Side Effects: Filesystem read (filemtime) untuk hash query string \u2014 cached oleh OPCache di production.

Cache-busting: query string ?v={mtime} otomatis berubah setiap kali file favicon di-replace,
memaksa browser refresh tanpa user perlu hard-reload.
--}}
@php
    $faviconPath = public_path('favicon.ico');
    $faviconVersion = file_exists($faviconPath) ? filemtime($faviconPath) : null;

    $faviconSvgPath = public_path('favicon.svg');
    $faviconSvgVersion = file_exists($faviconSvgPath) ? filemtime($faviconSvgPath) : null;

    $appleTouchPath = public_path('apple-touch-icon.png');
    $appleTouchVersion = file_exists($appleTouchPath) ? filemtime($appleTouchPath) : null;
@endphp

@if ($faviconSvgVersion)
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v={{ $faviconSvgVersion }}">
@endif

@if ($faviconVersion)
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}?v={{ $faviconVersion }}">
@endif

@if ($appleTouchVersion)
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}?v={{ $appleTouchVersion }}">
@endif
