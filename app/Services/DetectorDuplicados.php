<?php

namespace App\Services;

use App\Models\Hijo;
use App\Models\Padre;

/**
 * Detecta posibles registros duplicados de padres e hijos para frenar
 * el error humano al capturar. Compara normalizando (minusculas,
 * espacios y telefono solo-digitos) y devuelve arreglos planos listos
 * para flashear a sesion y mostrar en el aviso de confirmacion.
 */
class DetectorDuplicados
{
    public static function normalizarNombre(?string $valor): string
    {
        $valor = mb_strtolower(trim((string) $valor), 'UTF-8');

        return (string) preg_replace('/\s+/', ' ', $valor);
    }

    public static function normalizarTelefono(?string $valor): string
    {
        return (string) preg_replace('/\D+/', '', (string) $valor);
    }

    /**
     * Posibles padres duplicados. En edicion pasar $ignoreId para no
     * marcarse a si mismo.
     */
    public static function buscarPadres(string $nombre, ?string $telefono, ?string $email, ?int $ignoreId = null): array
    {
        $nombreNorm = self::normalizarNombre($nombre);
        $telNorm = self::normalizarTelefono($telefono);
        $emailNorm = mb_strtolower(trim((string) $email), 'UTF-8');

        if ($nombreNorm === '' && $telNorm === '' && $emailNorm === '') {
            return [];
        }

        $candidatos = Padre::query()
            ->withTrashed()
            ->with(['hijos' => fn ($q) => $q->withTrashed(), 'red'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($nombreNorm, $emailNorm) {
                $q->whereRaw('LOWER(nombre) = ?', [$nombreNorm]);
                if ($emailNorm !== '') {
                    $q->orWhereRaw('LOWER(email) = ?', [$emailNorm]);
                }
            })
            ->limit(20)
            ->get();

        // El telefono se compara normalizado en PHP (10 digitos con o sin
        // espacios, guiones, parentesis o +52 deben coincidir igual).
        if ($telNorm !== '') {
            $porTelefono = Padre::query()
                ->withTrashed()
                ->with(['hijos' => fn ($q) => $q->withTrashed(), 'red'])
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->whereNotNull('telefono')
                ->limit(200)
                ->get()
                ->filter(fn (Padre $p) => self::normalizarTelefono($p->telefono) === $telNorm
                    && self::normalizarTelefono($p->telefono) !== '');
            $candidatos = $candidatos->concat($porTelefono)->unique('id')->take(5);
        } else {
            $candidatos = $candidatos->take(5);
        }

        return $candidatos->map(function (Padre $p) use ($nombreNorm, $telNorm, $emailNorm) {
            $motivos = [];
            if ($emailNorm !== '' && mb_strtolower(trim((string) $p->email), 'UTF-8') === $emailNorm) {
                $motivos[] = 'Mismo correo electrónico';
            }
            if ($telNorm !== '' && self::normalizarTelefono($p->telefono) === $telNorm) {
                $motivos[] = 'Mismo teléfono';
            }
            if ($nombreNorm !== '' && self::normalizarNombre($p->nombre) === $nombreNorm) {
                $motivos[] = 'Mismo nombre';
            }

            return [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'uuid_short' => $p->uuid_short,
                'telefono' => $p->telefono,
                'email' => $p->email,
                'red' => $p->red?->descripcion,
                'hijos' => $p->hijos->pluck('nombre')->values()->all(),
                'motivos' => $motivos,
                'eliminado' => (bool) $p->trashed(),
            ];
        })->values()->all();
    }

    /**
     * Hijos ya registrados con mismo nombre + fecha de nacimiento.
     */
    public static function buscarHijos(string $nombre, string $fechaNacimiento, ?int $ignorePadreId = null, ?int $ignoreHijoId = null): array
    {
        $nombreNorm = self::normalizarNombre($nombre);

        if ($nombreNorm === '' || trim($fechaNacimiento) === '') {
            return [];
        }

        return Hijo::query()
            ->withTrashed()
            ->with(['padre' => fn ($q) => $q->withTrashed()])
            ->when($ignoreHijoId, fn ($q) => $q->where('id', '!=', $ignoreHijoId))
            ->when($ignorePadreId, fn ($q) => $q->where('padre_id', '!=', $ignorePadreId))
            ->whereDate('fecha_nacimiento', $fechaNacimiento)
            ->whereRaw('LOWER(nombre) = ?', [$nombreNorm])
            ->limit(5)
            ->get()
            ->map(fn (Hijo $h) => [
                'id' => $h->id,
                'nombre' => $h->nombre,
                'fecha_nacimiento' => $h->fecha_nacimiento?->format('d/m/Y'),
                'padre_id' => $h->padre_id,
                'padre_nombre' => $h->padre?->nombre,
                'padre_uuid' => $h->padre?->uuid_short,
                'eliminado' => (bool) $h->trashed(),
                'padre_eliminado' => (bool) $h->padre?->trashed(),
            ])
            ->values()->all();
    }

    /**
     * Revisa un registro completo de padre + hijos como llega del formulario.
     */
    public static function paraRegistroPadre(array $datos, array $hijos, ?int $ignoreId = null): array
    {
        $padres = self::buscarPadres(
            (string) ($datos['nombre'] ?? ''),
            $datos['telefono'] ?? null,
            $datos['email'] ?? null,
            $ignoreId
        );

        $encontrados = [];
        foreach ($hijos as $hijo) {
            if (! is_array($hijo)) {
                continue;
            }
            $nombreHijo = trim((string) ($hijo['nombre'] ?? ''));
            $fechaHijo = (string) ($hijo['fecha_nacimiento'] ?? '');
            if ($nombreHijo === '' || $fechaHijo === '') {
                continue;
            }
            foreach (self::buscarHijos($nombreHijo, $fechaHijo, $ignoreId) as $dup) {
                $dup['nombre_ingresado'] = $nombreHijo;
                $encontrados[] = $dup;
            }
        }

        return ['padres' => $padres, 'hijos' => $encontrados];
    }

    public static function hayDuplicados(array $resultado): bool
    {
        return count($resultado['padres'] ?? []) > 0 || count($resultado['hijos'] ?? []) > 0;
    }
}
