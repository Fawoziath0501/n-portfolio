<?php

return [

    /*
    | Compte administrateur créé par le seeder (php artisan db:seed).
    | En production, un mot de passe faible ou absent bloque le seeder.
    */
    'admin' => [
        // Adresse de l'administration (/admin par défaut). En production, choisissez une adresse difficile
        // à deviner, ex. ADMIN_PATH=gestion-7k2p9 : /admin répond alors « page introuvable ».
        'path' => trim((string) env('ADMIN_PATH', 'admin'), '/') ?: 'admin',
        'name' => env('ADMIN_NAME', 'Fawoziath SALOU'),
        'email' => env('ADMIN_EMAIL', 'saloufawoziath236@gmail.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
