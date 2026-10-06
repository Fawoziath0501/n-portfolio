@php
    $canonical = $page->base;
    $ogImage = $page->image ?: (($seo['ogImage'] ?? '') ?: ($data['profile']['photo'] ?? ''));
    if ($ogImage && ! str_starts_with($ogImage, 'http')) {
        $ogImage = $canonical.'/'.ltrim($ogImage, '/');
    }
    $siteName = $data['settings']['siteName'] ?? '';
    $url = $canonical.$page->alternates[$lang];
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    @if(($seo['indexable'] ?? true) === false || $maintenance || $notFound)
        <meta name="robots" content="noindex, nofollow">
    @else
        <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    @endif
    @if(! empty($seo['googleVerification']))
        <meta name="google-site-verification" content="{{ $seo['googleVerification'] }}">
    @endif
    @if(! empty($seo['bingVerification']))
        <meta name="msvalidate.01" content="{{ $seo['bingVerification'] }}">
    @endif
    @unless($notFound)
        {{-- Adresse canonique de la page et son équivalent dans l'autre langue --}}
        <link rel="canonical" href="{{ $url }}">
        <link rel="alternate" hreflang="fr" href="{{ $canonical.$page->alternates['fr'] }}">
        <link rel="alternate" hreflang="en" href="{{ $canonical.$page->alternates['en'] }}">
        <link rel="alternate" hreflang="x-default" href="{{ $canonical.$page->alternates['fr'] }}">
    @endunless
    @if(! empty($seo['favicon']))
        <link rel="icon" href="{{ $seo['favicon'] }}">
    @else
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @endif

    {{-- Aperçus de partage (LinkedIn, WhatsApp, Facebook, X) --}}
    <meta property="og:type" content="{{ $page->type === 'post' ? 'article' : 'website' }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $url }}">
    <meta property="og:locale" content="{{ $lang === 'en' ? 'en_GB' : 'fr_FR' }}">
    <meta property="og:locale:alternate" content="{{ $lang === 'en' ? 'fr_FR' : 'en_GB' }}">
    @if($page->type === 'post' && ! empty($page->item['datePublished']))
        <meta property="article:published_time" content="{{ $page->item['datePublished'] }}">
    @endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:alt" content="{{ $title }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif

    @if(($seo['structuredData'] ?? true) && ! $notFound)
        <script type="application/ld+json">{!! json_encode($page->schema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
    @include('partials.fonts', ['icons' => \App\Support\SiteIcons::names()])
    @vite(['resources/css/site.css', 'resources/js/site.js'])
</head>
<body>
    {{-- Contenu lisible sans JavaScript (moteurs de recherche, aperçus) ; remplacé par l'application Vue au chargement. --}}
    <div id="app">@unless($maintenance)@include('partials.prerender')@endunless</div>
    <script>window.__SITE__ = {!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};</script>
</body>
</html>
