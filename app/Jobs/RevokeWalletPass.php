<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RevokeWalletPass implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [15, 60, 300];

    public function __construct(public string $serial)
    {
        $this->onQueue('wallet');
    }

    /**
     * Revoca el pase remoto. Es best-effort: si WalletWallet no responde,
     * la limpieza local (borrar serial) ya dejó la credencial inutilizable
     * en nuestra base y el job se reintenta en segundo plano.
     */
    public function handle(): void
    {
        $key = config('services.walletwallet.key');
        if (! $key || $this->serial === '') {
            return;
        }

        $url = rtrim((string) config('services.walletwallet.url'), '/').'/passes/'.$this->serial;

        try {
            $response = Http::timeout(30)->withToken($key)->acceptJson()->delete($url);
        } catch (Throwable $e) {
            Log::warning('No se pudo contactar a WalletWallet para revocar el pase', [
                'serial' => $this->serial,
                'error' => $e->getMessage(),
            ]);

            $this->reintentar();

            return;
        }

        if ($response->successful() || $response->status() === 404) {
            Log::info('Pase wallet revocado', ['serial' => $this->serial, 'status' => $response->status()]);

            return;
        }

        if ($response->status() === 429 || $response->serverError()) {
            Log::warning('WalletWallet pidió reintentar la revocación', [
                'serial' => $this->serial,
                'status' => $response->status(),
            ]);

            $this->reintentar();

            return;
        }

        Log::warning('WalletWallet rechazó la revocación del pase', [
            'serial' => $this->serial,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    }

    private function reintentar(): void
    {
        if ($this->attempts() < $this->tries) {
            $this->release($this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)]);
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('Revocación de pase wallet agotó reintentos', [
            'serial' => $this->serial,
            'error' => $e->getMessage(),
        ]);
    }
}
