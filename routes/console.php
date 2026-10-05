<?php

use Illuminate\Support\Facades\Schedule;

// Corbeille : suppression définitive des éléments supprimés depuis plus de 30 jours.
// En production, ajoutez la tâche cron Laravel : * * * * * php artisan schedule:run
Schedule::command('model:prune')->daily();
