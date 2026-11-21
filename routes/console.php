<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('wallet:refresh {--padre= : ID de padre específico}', function () {
    $query = App\Models\Padre::query();

    if ($this->option('padre')) {
        $query->whereKey($this->option('padre'));
    } else {
        $query->whereNotNull('wallet_serial');
    }

    $count = 0;
    $query->chunk(100, function ($padres) use (&$count) {
        foreach ($padres as $padre) {
            \App\Jobs\GenerateWalletPass::dispatch($padre);
            $count++;
        }
    });

    $this->info("Se encolaron {$count} tarjetas para actualizar (PUT).");
})->purpose('Actualiza las tarjetas digitales ya emitidas con el nuevo diseño');

// Limpieza diaria de PDFs cacheados (evita crecer sin límite en Render).
Schedule::command('reportes:prune')->dailyAt('03:15');

// Higiene de la cola: trabajos fallidos de más de 7 días.
Schedule::command('queue:prune-failed --hours=168')->weekly();
