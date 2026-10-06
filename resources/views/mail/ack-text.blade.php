@php($en = $me['lang'] === 'en')
@php($isService = $msg->type === 'service')
@if($en)
Hello{{ $greetName ? ' '.$greetName : '' }},

@if($isService)
Thank you for your request{{ $serviceName ? ' about "'.$serviceName.'"' : '' }}. It has been received and I will review it carefully.

I will get back to you personally {{ $me['replyDelay'] }}, by email or by phone / WhatsApp, to discuss your project and the next steps: any questions, approach, timeline and quote.
@else
Thank you for taking the time to write to me. Your message has been received and I will read it carefully.

I will get back to you personally {{ $me['replyDelay'] }}, by email or by phone / WhatsApp.
@endif

SUMMARY
Reference: {{ $reference }}
Received on: {{ $receivedOn }}
Type: {{ $isService ? 'Service request' : 'Contact message' }}{{ $serviceName ? ' · '.$serviceName : '' }}
@if($msg->phone)
Phone provided: {{ $msg->phone }}
@endif

Something urgent? You can reach me directly by phone or WhatsApp.
See my work: {{ $me['projects'] }}

Kind regards,
@else
Bonjour{{ $greetName ? ' '.$greetName : '' }},

@if($isService)
Je vous remercie pour votre demande{{ $serviceName ? ' concernant « '.$serviceName.' »' : '' }}. Elle a bien été reçue et je vais l’étudier avec attention.

Je reviens vers vous personnellement sous {{ $me['replyDelay'] }}, par e-mail ou par téléphone / WhatsApp, pour échanger sur votre projet et la suite : précisions éventuelles, approche, délais et devis.
@else
Je vous remercie d’avoir pris le temps de m’écrire. Votre message a bien été reçu et je vais le lire avec attention.

Je reviens vers vous personnellement sous {{ $me['replyDelay'] }}, par e-mail ou par téléphone / WhatsApp.
@endif

RÉCAPITULATIF
Référence : {{ $reference }}
Reçu le : {{ $receivedOn }}
Objet : {{ $isService ? 'Demande de service' : 'Message de contact' }}{{ $serviceName ? ' · '.$serviceName : '' }}
@if($msg->phone)
Téléphone indiqué : {{ $msg->phone }}
@endif

Une urgence ? Vous pouvez me joindre directement par téléphone ou WhatsApp.
Découvrir mes réalisations : {{ $me['projects'] }}

Bien cordialement,
@endif
{{ $me['name'] }}
{{ $me['title'] }}{{ $me['location'] ? ' · '.$me['location'] : '' }}
@if($me['phone'])
{{ $en ? 'Phone:' : 'Tél. :' }} {{ $me['phone'] }}
@endif
@if($me['whatsapp'])
WhatsApp{{ $en ? ':' : ' :' }} {{ $me['whatsapp']['label'] }} ({{ $me['whatsapp']['url'] }})
@endif
@if($me['email'])
E-mail{{ $en ? ':' : ' :' }} {{ $me['email'] }}
@endif
{{ $me['site'] }}
