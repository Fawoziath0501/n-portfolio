Bonjour,

Voici la sauvegarde hebdomadaire de votre portfolio ({{ $name }}, {{ $size }}).

@if($attached)
Elle est jointe à cet e-mail : gardez-la dans un endroit sûr (Google Drive, clé USB…).
@else
Elle est trop lourde pour être jointe : téléchargez-la depuis l'administration, Paramètres → Sauvegardes.
@endif

Contenu : la base de données (textes, projets, articles, messages, réglages) et toutes les images de la médiathèque.
Pour restaurer, il faudra aussi la clé APP_KEY du fichier .env du serveur (non incluse, par sécurité).

Les 14 dernières sauvegardes sont aussi conservées sur le serveur.

Administration : {{ url('/'.config('portfolio.admin.path')) }}
