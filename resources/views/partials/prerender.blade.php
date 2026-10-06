{{-- Version lisible sans JavaScript de la page courante ($page : App\Support\SeoPage). --}}
<div class="pre">
    <header class="pre-hdr">
        <a href="/{{ $lang }}" class="pre-brand">{{ $data['settings']['siteName'] ?? '' }}</a>
        <nav class="pre-nav">
            @foreach($page->navLinks() as [$label, $href])
                <a href="{{ $href }}">{{ $label }}</a>
            @endforeach
            <a href="{{ $page->alternates[$lang === 'en' ? 'fr' : 'en'] }}" hreflang="{{ $lang === 'en' ? 'fr' : 'en' }}">{{ $lang === 'en' ? 'FR' : 'EN' }}</a>
        </nav>
    </header>
    <main class="pre-main">
        @if($page->crumbs)
            <nav class="pre-crumbs">
                <a href="/{{ $lang }}">{{ $data['labels']['nav0'][$lang] ?? 'Accueil' }}</a>
                @foreach($page->crumbs as [$label, $href])
                    / @if($loop->last)<span>{{ $label }}</span>@else<a href="{{ $href }}">{{ $label }}</a>@endif
                @endforeach
            </nav>
        @endif
        <h1>{{ $page->content['h1'] }}</h1>
        @if($page->content['intro'])
            <p class="pre-intro">{{ $page->content['intro'] }}</p>
        @endif
        @if($page->content['html'])
            <div class="rich">{!! $page->content['html'] !!}</div>
        @endif
        @foreach($page->content['links'] as $heading => $links)
            @if($links)
                <section>
                    <h2>{{ $heading }}</h2>
                    <ul>
                        @foreach($links as [$label, $href])
                            <li>@if($href)<a href="{{ $href }}">{{ $label }}</a>@else{{ $label }}@endif</li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endforeach
    </main>
</div>
