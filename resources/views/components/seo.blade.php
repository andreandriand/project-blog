@props([
    'title' => config('app.name'),
    'description' => 'A modern blog platform for sharing ideas, stories, and knowledge.',
    'image' => null,
    'url' => null,
    'type' => 'website',
    'article' => null,
    'canonical' => null,
    'noindex' => false,
])

@php
    $siteName = config('app.name');
    $currentUrl = $url ?? request()->url();
    $canonicalUrl = $canonical ?? $currentUrl;
    $ogImage = $image ?? asset('images/og-default.png');
    $metaDescription = Str::limit(strip_tags($description), 160);
@endphp

{{-- Basic Meta --}}
<meta name="description" content="{{ $metaDescription }}">
@if($noindex)
<meta name="robots" content="noindex, nofollow">
@else
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
@endif

{{-- Canonical --}}
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Open Graph --}}
<meta property="og:type" content="{{ $type }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $currentUrl }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="{{ app()->getLocale() === 'id' ? 'id_ID' : 'en_US' }}">
@if($ogImage)
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
@endif

{{-- Article specific --}}
@if($article)
<meta property="article:published_time" content="{{ $article['published_at'] ?? '' }}">
<meta property="article:modified_time" content="{{ $article['updated_at'] ?? '' }}">
<meta property="article:author" content="{{ $article['author'] ?? '' }}">
@if(!empty($article['category']))
<meta property="article:section" content="{{ $article['category'] }}">
@endif
@if(!empty($article['tags']))
@foreach($article['tags'] as $tag)
<meta property="article:tag" content="{{ $tag }}">
@endforeach
@endif
@endif

{{-- Twitter Card --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
@if($ogImage)
<meta name="twitter:image" content="{{ $ogImage }}">
@endif

{{-- JSON-LD Structured Data --}}
@if($type === 'article' && $article)
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $title,
    'description' => $metaDescription,
    'image' => $ogImage,
    'url' => $currentUrl,
    'datePublished' => $article['published_at'] ?? '',
    'dateModified' => $article['updated_at'] ?? '',
    'author' => [
        '@type' => 'Person',
        'name' => $article['author'] ?? '',
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $siteName,
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => $currentUrl,
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@elseif($type === 'website')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => $siteName,
    'url' => url('/'),
    'description' => $metaDescription,
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => url('/blog?search={search_term_string}'),
        'query-input' => 'required name=search_term_string',
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endif
