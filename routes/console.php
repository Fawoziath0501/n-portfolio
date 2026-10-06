<?php

use Illuminate\Support\Facades\Schedule;

/*
| Tâches planifiées. En production, une seule tâche Alwaysdata (toutes les heures) suffit :
|   cd ~/n-portfolio && php artisan schedule:run
*/
$tz = 'Africa/Porto-Novo';

// Corbeille : suppression définitive des éléments supprimés depuis plus de 30 jours.
Schedule::command('model:prune')->dailyAt('03:00')->timezone($tz);

// Sauvegarde chaque nuit, envoyée par e-mail le dimanche.
Schedule::command('portfolio:backup')->dailyAt('02:00')->timezone($tz)->when(fn () => ! now($tz)->isSunday());
Schedule::command('portfolio:backup --mail')->sundays()->at('02:00')->timezone($tz);
