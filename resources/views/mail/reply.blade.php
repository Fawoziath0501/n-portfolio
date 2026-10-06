{!! $replyBody !!}


———
{{ $original->lang === 'en' ? 'Your message of' : 'Votre message du' }} {{ $original->created_at?->format('d/m/Y') }} :

{!! preg_replace('/^/m', '> ', $original->body) !!}
