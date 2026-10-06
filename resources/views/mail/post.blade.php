@extends('mail.layout')
@php($en = $me['lang'] === 'en')

@section('title', $title)
@section('preheader', $excerpt)

@section('content')
<div style="font-family:'Courier New',monospace;font-size:12px;letter-spacing:1px;color:#2448C8;text-transform:uppercase;margin-bottom:10px;">{{ $en ? 'New article' : 'Nouvel article' }}</div>
@if($image)
<a href="{{ $link }}" style="display:block;margin:0 0 20px;"><img src="{{ $image }}" alt="{{ $title }}" width="536" style="display:block;width:100%;max-width:536px;height:auto;border-radius:8px;border:1px solid #E1E6F0;"></a>
@endif
<h1 style="margin:0 0 16px;font-size:24px;line-height:1.25;color:#0B1530;"><a href="{{ $link }}" style="color:#0B1530;text-decoration:none;">{{ $title }}</a></h1>
@if($excerpt)<p style="margin:0 0 24px;font-size:16px;line-height:1.6;">{{ $excerpt }}</p>@endif
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
  <tr><td style="border-radius:8px;background:#2448C8;">
    <a href="{{ $link }}" style="display:inline-block;padding:13px 22px;font-size:15px;font-weight:bold;color:#FFFFFF;text-decoration:none;">{{ $en ? 'Read the article' : 'Lire l’article' }} →</a>
  </td></tr>
</table>
<p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#55607A;">{{ $en ? 'A question or a project in mind? Simply reply to this email.' : 'Une question ou un projet en tête ? Répondez simplement à cet e-mail.' }}</p>
@endsection

@section('footer')
{{ $en ? 'You receive this email because you subscribed to the newsletter on '.$me['host'].'.' : 'Vous recevez cet e-mail car vous êtes inscrit(e) à la newsletter sur '.$me['host'].'.' }}
<br><a href="{{ $unsubscribe }}" style="color:#7A859C;">{{ $en ? 'Unsubscribe in one click' : 'Se désabonner en un clic' }}</a>
@endsection
