@extends('mail.layout')
@php($en = $me['lang'] === 'en')
@php($isService = $msg->type === 'service')

@section('title', $en ? ($isService ? 'Your request has been received' : 'Your message has been received') : ($isService ? 'Votre demande a bien été reçue' : 'Votre message a bien été reçu'))
@section('preheader', $en ? 'Thank you, I will get back to you personally '.$me['replyDelay'].'.' : 'Merci, je reviens vers vous personnellement sous '.$me['replyDelay'].'.')

@section('content')
<h1 style="margin:0 0 20px;font-size:24px;line-height:1.25;color:#0B1530;">
  @if($en){{ $isService ? 'Thank you, your request is in.' : 'Thank you, your message is in.' }}@else{{ $isService ? 'Merci, votre demande est bien arrivée.' : 'Merci, votre message est bien arrivé.' }}@endif
</h1>
<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">{{ $en ? 'Hello' : 'Bonjour' }}{{ $greetName ? ' '.$greetName : '' }},</p>
<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">
  @if($isService)
    @if($en)Thank you for your request{{ $serviceName ? ' about “'.$serviceName.'”' : '' }}. It has been received and I will review it carefully.@else Je vous remercie pour votre demande{{ $serviceName ? ' concernant « '.$serviceName.' »' : '' }}. Elle a bien été reçue et je vais l’étudier avec attention.@endif
  @else
    @if($en)Thank you for taking the time to write to me. Your message has been received and I will read it carefully.@else Je vous remercie d’avoir pris le temps de m’écrire. Votre message a bien été reçu et je vais le lire avec attention.@endif
  @endif
</p>
<p style="margin:0 0 24px;font-size:16px;line-height:1.6;">
  @if($isService)
    @if($en)I will get back to you personally <strong>{{ $me['replyDelay'] }}</strong>, by email or by phone / WhatsApp, to discuss your project and the next steps: any questions, approach, timeline and quote.@else Je reviens vers vous personnellement sous <strong>{{ $me['replyDelay'] }}</strong>, par e-mail ou par téléphone / WhatsApp, pour échanger sur votre projet et la suite : précisions éventuelles, approche, délais et devis.@endif
  @else
    @if($en)I will get back to you personally <strong>{{ $me['replyDelay'] }}</strong>, by email or by phone / WhatsApp.@else Je reviens vers vous personnellement sous <strong>{{ $me['replyDelay'] }}</strong>, par e-mail ou par téléphone / WhatsApp.@endif
  @endif
</p>

{{-- Récapitulatif (aucun texte libre du visiteur, hormis son numéro déjà contrôlé) --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F5F9;border-radius:10px;margin:0 0 24px;">
  <tr><td style="padding:18px 20px;font-size:14px;line-height:1.8;color:#0B1530;">
    <div style="font-family:'Courier New',monospace;font-size:12px;letter-spacing:1px;color:#2448C8;text-transform:uppercase;margin-bottom:6px;">{{ $en ? 'Summary' : 'Récapitulatif' }}</div>
    <div><span style="color:#55607A;">{{ $en ? 'Reference' : 'Référence' }}{{ $en ? ':' : ' :' }}</span> <strong>{{ $reference }}</strong></div>
    <div><span style="color:#55607A;">{{ $en ? 'Received on' : 'Reçu le' }}{{ $en ? ':' : ' :' }}</span> {{ $receivedOn }}</div>
    <div><span style="color:#55607A;">{{ $en ? 'Type' : 'Objet' }}{{ $en ? ':' : ' :' }}</span> {{ $isService ? ($en ? 'Service request' : 'Demande de service') : ($en ? 'Contact message' : 'Message de contact') }}@if($serviceName) · {{ $serviceName }}@endif</div>
    @if($msg->phone)<div><span style="color:#55607A;">{{ $en ? 'Phone provided' : 'Téléphone indiqué' }}{{ $en ? ':' : ' :' }}</span> {{ $msg->phone }}</div>@endif
  </td></tr>
</table>

<p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#55607A;">
  {{ $en ? 'Something urgent? You can reach me directly by phone or WhatsApp using the details below.' : 'Une urgence ? Vous pouvez me joindre directement par téléphone ou WhatsApp, avec les coordonnées ci-dessous.' }}
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
  <tr><td style="border-radius:8px;background:#2448C8;">
    <a href="{{ $me['projects'] }}" style="display:inline-block;padding:13px 22px;font-size:15px;font-weight:bold;color:#FFFFFF;text-decoration:none;">{{ $en ? 'See my work' : 'Découvrir mes réalisations' }} →</a>
  </td></tr>
</table>
@endsection

@section('footer')
{{ $en ? 'This email confirms a message sent from '.$me['host'].'. If you did not send it, you can simply ignore this email.' : 'Cet e-mail confirme l’envoi d’un message depuis '.$me['host'].'. Si vous n’en êtes pas à l’origine, vous pouvez simplement l’ignorer.' }}
@endsection
