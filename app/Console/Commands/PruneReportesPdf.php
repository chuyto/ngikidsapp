<?php

namespace App\Console\Commands;

use App\Services\ReportePdfCache;
use Illuminate\Console\Command;

class PruneReportesPdf extends Command
{
    protected $signature = 'reportes:prune';

    protected $description = 'Elimina los PDFs de reportes vencidos del almacenamiento local';

    public function handle(ReportePdfCache $cache): int
    {
        $eliminados = $cache->prune();

        $this->info("PDFs de reportes eliminados: {$eliminados}");

        return self::SUCCESS;
    }
}
