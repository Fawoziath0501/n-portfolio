@php($en = $me['lang'] === 'en')
@if($en)
Hello,

Your address is now subscribed to my newsletter. You will receive my next articles on web development, Laravel, Vue.js and search engine optimisation (SEO): practical advice, no advertising, and only when there is something worth sharing.

In the meantime, the latest articles are already available on the blog: {{ $me['blog'] }}

You can unsubscribe at any time: simply reply to this email.

Kind regards,
@else
Bonjour,

Votre adresse est bien inscrite à ma newsletter. Vous recevrez mes prochains articles sur le développement web, Laravel, Vue.js et le référencement naturel (SEO) : des conseils concrets, sans publicité, et seulement quand il y a quelque chose d’utile à partager.

En attendant, les derniers articles sont déjà disponibles sur le blog : {{ $me['blog'] }}

Vous pouvez vous désinscrire à tout moment : il suffit de répondre à cet e-mail.

Bien cordialement,
@endif
{{ $me['name'] }}
{{ $me['title'] }}{{ $me['location'] ? ' · '.$me['location'] : '' }}
{{ $me['site'] }}
