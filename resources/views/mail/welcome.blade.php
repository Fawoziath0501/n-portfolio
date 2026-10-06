@extends('mail.layout')
@php($en = $me['lang'] === 'en')

@section('title', $en ? 'Welcome to my newsletter' : 'Bienvenue dans ma newsletter')
@section('preheader', $en ? 'Your subscription is confirmed. Thank you!' : 'Votre inscription est confirmée. Merci !')

@section('content')
<h1 style="margin:0 0 20px;font-size:24px;line-height:1.25;color:#0B1530;">{{ $en ? 'Thank you for subscribing!' : 'Merci pour votre inscription !' }}</h1>
<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">{{ $en ? 'Hello,' : 'Bonjour,' }}</p>
<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
  {{ $en ? 'Your address is now subscribed to my newsletter. You will receive my next articles on web development, Laravel, Vue.js and search engine optimisation (SEO): practical advice, no advertising, and only when there is something worth sharing.' : 'Votre adresse est bien inscrite à ma newsletter. Vous recevrez mes prochains articles sur le développement web, Laravel, Vue.js et le référencement naturel (SEO) : des conseils concrets, sans publicité, et seulement quand il y a quelque chose d’utile à partager.' }}
</p>
<p style="margin:0 0 24px;font-size:16px;line-height:1.6;">
  {{ $en ? 'In the meantime, the latest articles are already available on the blog.' : 'En attendant, les derniers articles sont déjà disponibles sur le blog.' }}
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
  <tr><td style="border-radius:8px;background:#2448C8;">
    <a href="{{ $me['blog'] }}" style="display:inline-block;padding:13px 22px;font-size:15px;font-weight:bold;color:#FFFFFF;text-decoration:none;">{{ $en ? 'Read the blog' : 'Lire les articles du blog' }} →</a>
  </td></tr>
</table>
<p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#55607A;">
  @if($unsubscribe){{ $en ? 'You can unsubscribe at any time in one click, using the link at the bottom of each email.' : 'Vous pouvez vous désinscrire à tout moment, en un clic, grâce au lien en bas de chaque e-mail.' }}@else{{ $en ? 'You can unsubscribe at any time: simply reply to this email.' : 'Vous pouvez vous désinscrire à tout moment : il suffit de répondre à cet e-mail.' }}@endif
</p>
@endsection

@section('footer')
{{ $en ? 'You receive this email because this address was subscribed to the newsletter on '.$me['host'].'. If it was not you, simply reply and it will be removed.' : 'Vous recevez cet e-mail car cette adresse a été inscrite à la newsletter sur '.$me['host'].'. Si ce n’est pas vous, répondez simplement et elle sera retirée.' }}
@if($unsubscribe)<br><a href="{{ $unsubscribe }}" style="color:#7A859C;">{{ $en ? 'Unsubscribe in one click' : 'Se désabonner en un clic' }}</a>@endif
@endsection
