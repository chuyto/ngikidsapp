<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ReportePdfCache
{
    private const DIRECTORY = 'reportes/pdf';

    private const TTL_HOURS = 6;

    /**
     * Devuelve el PDF cacheado si sigue vigente.
     */
    public function get(string $key): ?string
    {
        $path = $this->path($key);
        $metaPath = $this->metaPath($key);

        if (! Storage::disk('local')->exists($path) || ! Storage::disk('local')->exists($metaPath)) {
            return null;
        }

        $meta = json_decode(Storage::disk('local')->get($metaPath), true);
        if (! is_array($meta) || ($meta['expires_at'] ?? 0) < now()->timestamp) {
            $this->forget($key);

            return null;
        }

        return Storage::disk('local')->get($path);
    }

    public function put(string $key, string $pdf, array $metadata = []): void
    {
        Storage::disk('local')->put($this->path($key), $pdf);
        Storage::disk('local')->put($this->metaPath($key), json_encode(array_merge($metadata, [
            'key' => $key,
            'created_at' => now()->timestamp,
            'expires_at' => now()->addHours(self::TTL_HOURS)->timestamp,
        ])));
    }

    public function forget(string $key): void
    {
        Storage::disk('local')->delete([$this->path($key), $this->metaPath($key)]);
    }

    /**
     * Invalida todos los PDFs generados. Se usa cuando cambia una asistencia,
     * se borra una familia o se corrige un registro.
     */
    public function flush(): void
    {
        Storage::disk('local')->deleteDirectory(self::DIRECTORY);
    }

    /**
     * Elimina PDFs vencidos y metadatos huérfanos.
     */
    public function prune(): int
    {
        $deleted = 0;
        $now = now()->timestamp;

        foreach (Storage::disk('local')->files(self::DIRECTORY) as $file) {
            if (! str_ends_with($file, '.json')) {
                continue;
            }

            $meta = json_decode(Storage::disk('local')->get($file), true);
            if (! is_array($meta) || ($meta['expires_at'] ?? 0) < $now) {
                $base = preg_replace('/\.json$/', '', $file);
                Storage::disk('local')->delete([$file, $base.'.pdf']);
                $deleted++;
            }
        }

        return $deleted;
    }

    private function path(string $key): string
    {
        return self::DIRECTORY.'/'.$key.'.pdf';
    }

    private function metaPath(string $key): string
    {
        return self::DIRECTORY.'/'.$key.'.json';
    }
}
