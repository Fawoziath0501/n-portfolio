<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Administration · {{ trim(($owner->first_name ?? '').' '.($owner->last_name ?? '')) }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @include('partials.fonts')
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
    <div id="app"></div>
    <script>window.__ADMIN_BASE__ = @json($base ?? '/admin'); window.__LOGIN_CAPTCHA__ = @json(\App\Support\Captcha::protects('login') ? \App\Support\Captcha::forPublic() : null);</script>
</body>
</html>
