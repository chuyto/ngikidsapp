<?php

namespace App\Services;

use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Log;

class CloudinaryAssetCleaner
{
    /**
     * Borra un asset de Cloudinary de forma best-effort. Si falla, se
     * registra pero no se interrumpe la operación de negocio.
     */
    public function delete(?string $publicId, ?string $url = null): void
    {
        $publicId = $publicId ?: $this->publicIdFromUrl($url);

        if (! $publicId) {
            return;
        }

        try {
            Cloudinary::uploadApi()->destroy($publicId, [
                'resource_type' => 'image',
                'invalidate' => true,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo borrar un asset de Cloudinary', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deriva el public_id de una URL de entrega de Cloudinary. Es un
     * fallback para registros viejos que no guardaron el public_id.
     */
    public function publicIdFromUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! str_contains($path, '/upload/')) {
            return null;
        }

        $rest = explode('/upload/', $path, 2)[1];
        $rest = ltrim($rest, '/');

        // Quita transformaciones y versión: w_500,q_auto/v123/carpeta/id.png
        if (preg_match('#(?:^|/)(v\d+)/(.+)$#', $rest, $matches)) {
            $rest = $matches[2];
        } else {
            $rest = preg_replace('#^(?:[a-z]{1,4}_[^/]+/)+#i', '', $rest) ?? $rest;
        }

        $rest = preg_replace('/\.[a-z0-9]+$/i', '', $rest) ?? $rest;

        return $rest !== '' ? $rest : null;
    }
}
