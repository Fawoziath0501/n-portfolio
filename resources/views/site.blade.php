@php
    $fullName = trim(($profile['firstName'] ?? '').' '.($profile['middleName'] ?? '').' '.($profile['lastName'] ?? ''));
    $canonical = rtrim($seo['canonical'] ?? '', '/') ?: url('/');
    $ogImage = $seo['ogImage'] ?? '';
    $socials = collect($profile['socials'] ?? [])->filter(fn ($s) => ($s['visible'] ?? false) && ! empty($s['url']))->pluck('url')->values();
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    @if(($seo['indexable'] ?? true) === false || $maintenance)
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ $canonical.(request()->path() === '/' ? '/' : '/'.request()->path()) }}">
    <link rel="alternate" hreflang="fr" href="{{ $canonical }}/fr">
    <link rel="alternate" hreflang="en" href="{{ $canonical }}/en">
    @if(! empty($seo['favicon']))
        <link rel="icon" href="{{ $seo['favicon'] }}">
    @else
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:locale" content="{{ $lang === 'en' ? 'en_GB' : 'fr_FR' }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    @if($seo['structuredData'] ?? true)
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $fullName,
            'jobTitle' => \App\Support\Portfolio::tx($profile['title'] ?? '', $lang),
            'email' => 'mailto:'.($profile['email'] ?? ''),
            'telephone' => $profile['phone'] ?? '',
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => \App\Support\Portfolio::tx($profile['location'] ?? '', $lang)],
            'url' => $canonical,
            'sameAs' => $socials,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endif
    @include('partials.fonts')
    @vite(['resources/css/site.css', 'resources/js/site.js'])
</head>
<body>
    <div id="app"></div>
    <script>window.__SITE__ = {!! json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};</script>
</body>
</html>
