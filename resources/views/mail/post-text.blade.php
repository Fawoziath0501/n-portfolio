@php($en = $me['lang'] === 'en')
{{ $en ? 'NEW ARTICLE' : 'NOUVEL ARTICLE' }}

{{ $title }}

{{ $excerpt }}

{{ $en ? 'Read the article' : 'Lire l’article' }} : {{ $link }}

{{ $en ? 'A question or a project in mind? Simply reply to this email.' : 'Une question ou un projet en tête ? Répondez simplement à cet e-mail.' }}

{{ $me['name'] }}
{{ $me['title'] }}
{{ $me['site'] }}

{{ $en ? 'Unsubscribe in one click' : 'Se désabonner en un clic' }} : {{ $unsubscribe }}
