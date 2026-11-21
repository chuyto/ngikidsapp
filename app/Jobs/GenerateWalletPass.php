<?php

namespace App\Jobs;

use App\Models\Padre;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateWalletPass implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [15, 60, 300];

    public function __construct(public Padre $padre, public ?string $notification = null)
    {
        $this->onQueue('wallet');
    }

    public function handle(): void
    {
        $key = config('services.walletwallet.key');
        if (! $key) {
            throw new \RuntimeException('WALLETWALLET_API_KEY no está configurada en el entorno de producción.');
        }

        $padre = $this->padre;

        // Una familia en papelera o con consentimiento revocado no debe
        // recibir tarjetas nuevas.
        if ($padre->trashed() || $padre->privacidad_revocada_at) {
            return;
        }

        $baseUrl = rtrim((string) config('services.walletwallet.url'), '/').'/passes';
        $body = $this->buildPayload($padre);

        try {
            // Si el padre ya tiene una tarjeta emitida, la actualizamos en el
            // mismo serial (PUT): los dispositivos la refrescan sin crear una
            // tarjeta nueva.
            $response = $padre->wallet_serial
                ? Http::timeout(30)->withToken($key)->acceptJson()->put($baseUrl.'/'.$padre->wallet_serial, $body)
                : Http::timeout(30)->withToken($key)->acceptJson()->post($baseUrl, $body);
        } catch (\Throwable $e) {
            Log::warning('No se pudo contactar a WalletWallet', [
                'uuid_short' => $padre->uuid_short,
                'error' => $e->getMessage(),
            ]);

            $this->reintentar();

            return;
        }

        if ($response->successful()) {
            $data = $response->json();

            $padre->update([
                'wallet_serial' => $data['serialNumber'] ?? $padre->wallet_serial,
                'wallet_share_url' => $data['shareUrl'] ?? $padre->wallet_share_url,
                'wallet_issued_at' => now(),
                'wallet_revocado_at' => null,
            ]);

            Log::info('Pase wallet emitido para '.$padre->uuid_short.' (serial: '.($data['serialNumber'] ?? $padre->wallet_serial ?? 'n/a').')');

            return;
        }

        // 429 y 5xx son transitorios: se reintenta con backoff. Los 4xx de
        // validación no mejoran reintentando, así que solo se registran.
        if ($response->status() === 429 || $response->serverError()) {
            Log::warning('WalletWallet pidió reintentar', [
                'uuid_short' => $padre->uuid_short,
                'status' => $response->status(),
            ]);

            $this->reintentar();

            return;
        }

        Log::error('Error WalletWallet al emitir pase: '.$response->status().' '.$response->body());
        $this->fail(new \RuntimeException('WalletWallet error: '.$response->status()));
    }

    private function reintentar(): void
    {
        if ($this->attempts() < $this->tries) {
            $this->release($this->backoff[min($this->attempts() - 1, count($this->backoff) - 1)]);
        }
    }

    /**
     * Construye el payload de la credencial digital (Apple + Google Wallet
     * se generan desde el mismo cuerpo en WalletWallet).
     */
    protected function buildPayload(Padre $padre): array
    {
        $body = [
            'barcodeValue' => $this->qrValue($padre),
            'barcodeFormat' => 'QR',
            // Texto vacio: ningun wallet imprime nada bajo el QR.
            'barcodeAltText' => '',
            'logoText' => 'NGI Kids',
            'organizationName' => 'NGI Kids',
            'description' => 'Credencial digital NGI Kids — control de asistencia.',
            // Plan Free de WalletWallet: el color personalizado ('color') y el
            // logo ('logoURL'/'iconURL') son Pro-only (400 si se envian).
            // Se usa el preset gratis mas cercano al naranja de marca.
            'colorPreset' => 'orange',
            'primaryFields' => [
                ['label' => 'CREDENCIAL DIGITAL', 'value' => $padre->nombre],
            ],
            'headerFields' => [
                ['label' => 'ID', 'value' => $padre->uuid_short],
            ],
            'secondaryFields' => $this->kidsFields($padre),
            'backFields' => $this->backFields($padre),
        ];

        return $body;
    }

    /**
     * Valor del QR: el UID plano de 6 caracteres. Minimalista, escaneo
     * rapido y sin links impresos (ver barcodeAltText vacio). El lector
     * de la pantalla de asistencia acepta el UID directo; por
     * compatibilidad tambien entiende URLs con ?codigo= de pases viejos.
     */
    protected function qrValue(Padre $padre): string
    {
        return $padre->uuid_short;
    }

    /**
     * Filas de NIÑO/NIÑOS bajo el titular. Un solo hijo → "NIÑO".
     */
    protected function kidsFields(Padre $padre): array
    {
        $nombres = $padre->hijos()
            ->pluck('nombre')
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->values();

        if ($nombres->isEmpty()) {
            return [];
        }

        $rows = [
            ['label' => $nombres->count() === 1 ? 'NIÑO' : 'NIÑOS', 'value' => $nombres->first()],
        ];

        foreach ($nombres->slice(1) as $nombre) {
            $rows[] = ['label' => '', 'value' => $nombre];
        }

        return $rows;
    }

    /**
     * Info secundaria al reverso de la tarjeta.
     */
    protected function backFields(Padre $padre): array
    {
        $fields = [
            ['label' => 'PADRE/RESPONSABLE', 'value' => $padre->nombre],
            ['label' => 'PARENTESCO', 'value' => $padre->parentesco ?: 'Responsable'],
            ['label' => 'TELÉFONO', 'value' => $padre->telefono ?: '—'],
        ];

        if ($padre->red) {
            $fields[] = ['label' => 'RED', 'value' => $padre->red->descripcion];
        }

        $fields[] = ['label' => 'CÓDIGO CREDENCIAL', 'value' => $padre->uuid_short];
        $fields[] = ['label' => 'EMITIDO', 'value' => now()->format('d/m/Y')];

        if ($this->notification) {
            $fields[] = [
                'label' => 'ÚLTIMA ACTUALIZACIÓN',
                'value' => $this->notification,
                'changeMessage' => '%@',
            ];
        }

        return $fields;
    }
}
