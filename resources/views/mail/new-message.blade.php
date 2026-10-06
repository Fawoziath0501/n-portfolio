{{ $msg->type === 'service' ? 'Demande de service' : 'Message de contact' }} reçu sur le portfolio.

Nom : {!! $msg->name !!}
E-mail : {!! $msg->email !!}
@if($msg->phone)Téléphone : {!! $msg->phone !!}
@endif
@if($msg->subject)Sujet : {!! $msg->subject !!}
@endif

{!! $msg->body !!}

Répondre depuis l'administration : {{ url('/admin/messages') }}
