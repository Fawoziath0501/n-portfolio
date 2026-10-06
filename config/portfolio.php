<?php

return [

    /*
    | Compte administrateur créé par le seeder (php artisan db:seed).
    | En production, un mot de passe faible ou absent bloque le seeder.
    */
    'admin' => [
        'name' => env('ADMIN_NAME', 'Fawoziath SALOU'),
        'email' => env('ADMIN_EMAIL', 'saloufawoziath236@gmail.com'),
        'password' => env('ADMIN_PASSWORD'),
    ],

];
