<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateWalletPass;
use App\Models\Asistencia;
use App\Models\AsistenciaCambio;
use App\Models\AsistenciaHijo;
use App\Models\Hijo;
use App\Models\Padre;
use App\Models\Servicio;
use App\Services\AuditLogger;
use App\Services\CloudinaryAssetCleaner;
use App\Services\ReportePdfCache;
use Carbon\Carbon;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AsistenciaController extends Controller
{
    /**
     * Muestra la interfaz de asistencia.
     */
    public function create()
    {
        $servicioActivo = Servicio::where('activo', true)->first();

        return view('asistencias.create', compact('servicioActivo'));
    }

    /**
     * Procesa entrada y salida. La firma se sube FUERA de la transacción
     * para no mantener bloqueada la fila del servicio durante una llamada
     * externa a Cloudinary.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'uuid_short' => [
                'required',
                'string',
                'size:6',
                Rule::exists('padres', 'uuid_short')->where(fn ($query) => $query->whereNull('deleted_at')),
            ],
            'hijos' => ['nullable', 'array'],
            'hijos.*' => ['array'],
            'hijos.*.status' => ['nullable', 'in:asistio,no_asistio'],
            'hijos.*.ficha' => ['nullable', 'string', 'max:50'],
            'hijos_asistencia' => ['nullable', 'array'], // Compatibilidad con clientes viejos.
            'hijos_asistencia.*' => ['array'],
            'hijos_asistencia.*.status' => ['nullable', 'in:asistio,no_asistio'],
            'hijos_asistencia.*.ficha' => ['nullable', 'string', 'max:50'],
            'signature' => ['required', 'string', 'max:3000000', 'regex:/^data:image\/(png|jpeg);base64,/i'],
        ]);

        $padre = Padre::with(['hijos' => fn ($query) => $query->orderBy('nombre')->orderBy('id')])
            ->where('uuid_short', $validated['uuid_short'])
            ->firstOrFail();

        $servicio = Servicio::where('activo', true)->first();
        if (! $servicio) {
            return response()->json(['error' => 'No hay un servicio activo para registrar asistencia.'], 422);
        }

        $asistencia = Asistencia::where('uuid_short', $validated['uuid_short'])
            ->where('servicio_id', $servicio->id)
            ->first();

        if ($asistencia && $asistencia->salida_at) {
            return response()->json(['error' => 'La salida de este folio ya fue registrada.'], 422);
        }

        $esSalida = (bool) $asistencia;
        $registros = $esSalida ? [] : $this->normalizarRegistros($request, $padre);

        $firma = null;

        try {
            $firma = $this->subirFirma($validated['signature'], $validated['uuid_short']);

            $resultado = DB::transaction(function () use ($servicio, $registros, $esSalida, $firma, $validated) {
                // Serializa las operaciones del mismo servicio sin mantener
                // el lock durante la subida a Cloudinary.
                Servicio::whereKey($servicio->id)->lockForUpdate()->firstOrFail();

                $actual = Asistencia::where('uuid_short', $validated['uuid_short'])
                    ->where('servicio_id', $servicio->id)
                    ->lockForUpdate()
                    ->first();

                if ($esSalida) {
                    if (! $actual) {
                        return ['error' => 'No hay una entrada registrada para este folio.'];
                    }

                    if ($actual->salida_at) {
                        return ['error' => 'La salida de este folio ya fue registrada.'];
                    }

                    $ahora = now();
                    $json = $actual->hijos_asistencia ?? [];
                    $json['url_firma_salida'] = $firma['url'];

                    $actual->update([
                        'hora_salida' => $ahora->format('H:i:s'),
                        'salida_at' => $ahora,
                        'salida_registrada_por_id' => auth()->id(),
                        'firma_salida_public_id' => $firma['public_id'],
                        'hijos_asistencia' => $json,
                    ]);

                    $this->asegurarRegistros($actual);

                    foreach ($actual->registros()->get() as $registro) {
                        if ($registro->estado !== 'asistio' || $registro->salida_at) {
                            continue;
                        }

                        $antes = $registro->toArray();
                        $registro->update([
                            'salida_at' => $ahora,
                            'salida_registrada_por_id' => auth()->id(),
                        ]);
                        $this->registrarCambio($actual, 'salida', $antes, $registro->fresh()->toArray());
                    }

                    $actual->refresh()->load('registros');
                    $actual->sincronizarJsonLegacy();

                    $nombres = $actual->registros
                        ->where('estado', 'asistio')
                        ->pluck('nombre')
                        ->implode(', ');

                    return [
                        'success' => '¡Salida y entrega registrada correctamente!',
                        'wallet_notification' => 'Salida registrada'.($nombres ? " para {$nombres}" : '').' · '.$servicio->descripcion_servicio.' · '.$ahora->format('d/m H:i').'.',
                    ];
                }

                if ($actual) {
                    return ['error' => 'Ya existe una entrada registrada para este folio.'];
                }

                $fichas = collect($registros)->pluck('ficha')->filter()->values();
                $duplicadas = $fichas->duplicates();
                if ($duplicadas->isNotEmpty()) {
                    return ['error' => 'No se puede asignar la misma ficha a más de un niño.'];
                }

                if ($fichas->isNotEmpty()) {
                    $ocupada = AsistenciaHijo::where('servicio_id', $servicio->id)
                        ->whereIn('ficha', $fichas->all())
                        ->value('ficha');

                    if ($ocupada !== null) {
                        return ['error' => "La ficha #{$ocupada} ya fue asignada a otro niño."];
                    }
                }

                $ahora = now();
                $asistencia = Asistencia::create([
                    'uuid_short' => $validated['uuid_short'],
                    'servicio_id' => $servicio->id,
                    'hora_entrada' => $ahora->format('H:i:s'),
                    'entrada_at' => $ahora,
                    'registrado_por_id' => auth()->id(),
                    'firma_entrada_public_id' => $firma['public_id'],
                    'hijos_asistencia' => ['url_firma_entrada' => $firma['url']],
                ]);

                foreach ($registros as $datos) {
                    $asistencia->registros()->create([
                        'servicio_id' => $servicio->id,
                        'hijo_id' => $datos['hijo_id'],
                        'nombre' => $datos['nombre'],
                        'estado' => $datos['estado'],
                        'ficha' => $datos['ficha'],
                        'entrada_at' => $datos['estado'] === 'asistio' ? $ahora : null,
                        'registrado_por_id' => auth()->id(),
                    ]);
                }

                $asistencia->load('registros');
                $asistencia->sincronizarJsonLegacy();

                $this->registrarCambio(
                    $asistencia,
                    'entrada',
                    [],
                    $asistencia->registros->toArray(),
                );

                $nombres = collect($registros)
                    ->where('estado', 'asistio')
                    ->pluck('nombre')
                    ->implode(', ');

                return [
                    'success' => '¡Ingreso registrado con éxito!',
                    'wallet_notification' => 'Entrada registrada'.($nombres ? " para {$nombres}" : '').' · '.$servicio->descripcion_servicio.' · '.$ahora->format('d/m H:i').'.',
                ];
            }, 3);
        } catch (ValidationException $e) {
            $this->borrarFirma($firma);

            throw $e;
        } catch (QueryException $e) {
            $this->borrarFirma($firma);

            Log::error('Error de base de datos registrando asistencia', [
                'uuid_short' => $validated['uuid_short'],
                'servicio_id' => $servicio->id,
                'exception' => $e,
            ]);

            $mensaje = str_contains(strtolower($e->getMessage()), 'ficha')
                ? 'La ficha seleccionada ya fue asignada. Recarga e inténtalo de nuevo.'
                : 'No fue posible registrar la asistencia. Inténtalo nuevamente.';

            return response()->json(['error' => $mensaje], 422);
        } catch (Exception $e) {
            $this->borrarFirma($firma);

            Log::error('Error registrando asistencia', [
                'uuid_short' => $validated['uuid_short'],
                'servicio_id' => $servicio->id,
                'exception' => $e,
            ]);

            return response()->json(['error' => 'No fue posible registrar la asistencia. Inténtalo nuevamente.'], 500);
        }

        if (isset($resultado['error'])) {
            $this->borrarFirma($firma);

            return response()->json($resultado, 422);
        }

        app(ReportePdfCache::class)->flush();

        if (isset($resultado['wallet_notification'])) {
            $padre = Padre::where('uuid_short', $validated['uuid_short'])->first();
            if ($padre?->wallet_serial) {
                GenerateWalletPass::dispatch($padre, $resultado['wallet_notification']);
            }
            unset($resultado['wallet_notification']);
        }

        return response()->json($resultado, 200);
    }

    /**
     * Buscador AJAX para obtener datos del padre y sus registros por niño.
     */
    public function search(Request $request)
    {
        $request->validate([
            'uuid_short' => ['required', 'string', 'size:6'],
        ]);

        $padre = Padre::with(['hijos', 'red'])->where('uuid_short', $request->uuid_short)->first();

        if (! $padre) {
            return response()->json(['padre' => null]);
        }

        $servicio = Servicio::where('activo', true)->first();
        $asistencia = $servicio
            ? Asistencia::where('uuid_short', $padre->uuid_short)
                ->where('servicio_id', $servicio->id)
                ->first()
            : null;

        $registros = [];

        if ($asistencia) {
            $this->asegurarRegistros($asistencia);
            $asistencia->load('registros');

            foreach ($asistencia->registros as $registro) {
                $llave = $registro->hijo_id ?: 'n:'.$registro->nombre;
                $registros[$llave] = [
                    'id' => $registro->id,
                    'hijo_id' => $registro->hijo_id,
                    'nombre' => $registro->nombre,
                    'estado' => $registro->estado,
                    'ficha' => $registro->ficha,
                    'entrada_at' => $registro->entrada_at?->toIso8601String(),
                    'salida_at' => $registro->salida_at?->toIso8601String(),
                ];
            }
        }

        return response()->json([
            'padre' => [
                'nombre' => $padre->nombre,
                'red' => $padre->red,
                'telefono' => $padre->telefono,
                'foto_padre' => $padre->foto_padre,
                'uuid_short' => $padre->uuid_short,
                'hijos' => $padre->hijos->map(fn (Hijo $hijo) => [
                    'id' => $hijo->id,
                    'nombre' => $hijo->nombre,
                    'edad' => $hijo->edad,
                ])->values(),
                'hora_entrada' => $asistencia?->entrada_at?->toIso8601String(),
                'hora_salida' => $asistencia?->salida_at?->toIso8601String(),
                'registros' => $registros,
                // Compatibilidad con la vista anterior; se puede retirar
                // cuando todos los clientes estén actualizados.
                'hijos_asistencia' => collect($registros)
                    ->mapWithKeys(fn ($registro) => [
                        $registro['nombre'] => [
                            'status' => $registro['estado'],
                            'ficha' => $registro['ficha'],
                        ],
                    ])
                    ->all(),
            ],
        ]);
    }

    /**
     * Formulario de corrección de una asistencia (solo asistencias.editar).
     */
    public function edit(Asistencia $asistencia)
    {
        $asistencia->load(['padre', 'servicio', 'registros.hijo', 'cambios.user']);
        $this->asegurarRegistros($asistencia);
        $asistencia->load(['registros.hijo', 'cambios.user']);

        return view('asistencias.edit', compact('asistencia'));
    }

    /**
     * Corrige estado/ficha/salida de los registros por niño y deja bitácora.
     */
    public function update(Request $request, Asistencia $asistencia)
    {
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
            'registros' => ['required', 'array', 'min:1'],
            'registros.*.estado' => ['required', 'in:asistio,no_asistio'],
            'registros.*.ficha' => ['nullable', 'string', 'max:50'],
            'registros.*.salida_at' => ['nullable', 'date'],
        ]);

        $asistencia->load('registros');
        $this->asegurarRegistros($asistencia);
        $asistencia->load('registros');

        $registros = $asistencia->registros->keyBy('id');
        $ids = collect(array_keys($validated['registros']))->map(fn ($id) => (int) $id);

        if ($ids->diff($registros->keys())->isNotEmpty()) {
            throw ValidationException::withMessages([
                'registros' => 'Uno de los registros no pertenece a esta asistencia.',
            ]);
        }

        $fichas = collect($validated['registros'])
            ->filter(fn ($datos) => $datos['estado'] === 'asistio')
            ->map(fn ($datos) => $this->normalizarFicha($datos['ficha'] ?? null))
            ->filter()
            ->values();

        if ($fichas->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'registros' => 'No se puede asignar la misma ficha a más de un niño.',
            ]);
        }

        $ocupada = AsistenciaHijo::where('servicio_id', $asistencia->servicio_id)
            ->whereIn('ficha', $fichas->all())
            ->whereNotIn('id', $ids->all())
            ->value('ficha');

        if ($ocupada !== null) {
            throw ValidationException::withMessages([
                'registros' => "La ficha #{$ocupada} ya está asignada a otro niño.",
            ]);
        }

        DB::transaction(function () use ($asistencia, $validated, $registros) {
            foreach ($validated['registros'] as $id => $datos) {
                $registro = $registros->get((int) $id);
                if (! $registro) {
                    continue;
                }

                $antes = $registro->toArray();
                $ficha = $this->normalizarFicha($datos['ficha'] ?? null);
                $estado = $datos['estado'];

                if ($estado === 'no_asistio') {
                    $ficha = null;
                    $entradaAt = null;
                    $salidaAt = null;
                } else {
                    $entradaAt = $registro->entrada_at ?: $asistencia->entrada_at ?: now();
                    $salidaAt = ! empty($datos['salida_at'])
                        ? Carbon::parse($datos['salida_at'])
                        : $registro->salida_at;

                    if ($salidaAt && $salidaAt->lt($entradaAt)) {
                        throw ValidationException::withMessages([
                            'registros' => 'La salida no puede ser anterior a la entrada.',
                        ]);
                    }
                }

                $registro->update([
                    'estado' => $estado,
                    'ficha' => $ficha,
                    'entrada_at' => $entradaAt,
                    'salida_at' => $salidaAt,
                    'corregido_por_id' => auth()->id(),
                    'motivo_correccion' => $validated['motivo'],
                ]);

                $this->registrarCambio(
                    $asistencia,
                    'correccion',
                    $antes,
                    $registro->fresh()->toArray(),
                    $validated['motivo'],
                    $registro->id,
                );
            }

            $asistencia->refresh()->load('registros');
            $asistencia->sincronizarJsonLegacy();

            AuditLogger::log(
                'asistencia.corregida',
                $asistencia,
                'Corrección de asistencia: '.$validated['motivo'],
                [],
                ['registros' => $asistencia->registros->toArray()],
            );
        });

        app(ReportePdfCache::class)->flush();

        return redirect()
            ->route('asistencias.edit', $asistencia)
            ->with('success', 'Asistencia corregida y registrada en la bitácora.');
    }

    /**
     * Anula una asistencia (soft delete) con motivo y auditoría.
     */
    public function destroy(Request $request, Asistencia $asistencia)
    {
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        $asistencia->update([
            'anulada_por_id' => auth()->id(),
            'motivo_anulacion' => $validated['motivo'],
        ]);

        $this->registrarCambio(
            $asistencia,
            'anulacion',
            ['activa' => true],
            ['activa' => false],
            $validated['motivo'],
        );

        AuditLogger::log(
            'asistencia.anulada',
            $asistencia,
            'Anulación de asistencia: '.$validated['motivo'],
        );

        $padreId = Padre::withTrashed()->where('uuid_short', $asistencia->uuid_short)->value('id');
        $asistencia->delete();
        app(ReportePdfCache::class)->flush();

        return redirect()
            ->to($padreId ? route('padres.show', $padreId) : route('reportes.index'))
            ->with('success', 'Asistencia anulada correctamente.');
    }

    /**
     * Redirección al reporte del servicio activo.
     */
    public function reporteServicioActivo()
    {
        $servicio = Servicio::where('activo', true)->first();

        if (! $servicio) {
            return redirect()->back()->with('error', 'Debe activar un servicio primero.');
        }

        return redirect()->route('reportes.index', ['servicio_id' => $servicio->id]);
    }

    /**
     * @return array<int, array{hijo_id: int, nombre: string, estado: string, ficha: string|null}>
     */
    private function normalizarRegistros(Request $request, Padre $padre): array
    {
        $hijos = $padre->hijos->keyBy('id');
        $registros = $hijos->map(fn (Hijo $hijo) => [
            'hijo_id' => $hijo->id,
            'nombre' => $hijo->nombre,
            'estado' => 'no_asistio',
            'ficha' => null,
        ])->all();

        $nuevos = $request->input('hijos', []);
        $legacy = $request->input('hijos_asistencia', []);

        if (is_array($nuevos) && $nuevos !== []) {
            foreach ($nuevos as $id => $datos) {
                $id = (int) $id;
                if (! isset($registros[$id])) {
                    throw ValidationException::withMessages([
                        'hijos' => 'Uno de los niños seleccionados no pertenece a este padre.',
                    ]);
                }

                $estado = $datos['status'] ?? 'no_asistio';
                $registros[$id]['estado'] = $estado;
                $registros[$id]['ficha'] = $estado === 'asistio'
                    ? $this->normalizarFicha($datos['ficha'] ?? null)
                    : null;
            }
        } elseif (is_array($legacy) && $legacy !== []) {
            foreach ($legacy as $nombre => $datos) {
                if (str_starts_with((string) $nombre, 'url_firma_')) {
                    continue;
                }

                $coincidencias = $padre->hijos->where('nombre', trim((string) $nombre));
                if ($coincidencias->count() !== 1) {
                    throw ValidationException::withMessages([
                        'hijos' => "El niño \"{$nombre}\" no está registrado o su nombre es ambiguo. Corrige el registro del padre.",
                    ]);
                }

                $hijo = $coincidencias->first();
                $estado = $datos['status'] ?? 'no_asistio';
                $registros[$hijo->id]['estado'] = $estado;
                $registros[$hijo->id]['ficha'] = $estado === 'asistio'
                    ? $this->normalizarFicha($datos['ficha'] ?? null)
                    : null;
            }
        }

        $asistentes = collect($registros)->where('estado', 'asistio');
        if ($asistentes->isEmpty()) {
            throw ValidationException::withMessages([
                'hijos' => 'Debe marcar al menos a un niño para registrar la asistencia.',
            ]);
        }

        $duplicadas = $asistentes->pluck('ficha')->filter()->duplicates();
        if ($duplicadas->isNotEmpty()) {
            throw ValidationException::withMessages([
                'hijos' => 'No se puede asignar la misma ficha a más de un niño.',
            ]);
        }

        return array_values($registros);
    }

    private function normalizarFicha(mixed $ficha): ?string
    {
        $ficha = trim((string) ($ficha ?? ''));

        return $ficha === '' ? null : $ficha;
    }

    /**
     * Crea registros por niño para asistencias históricas que solo tienen JSON.
     */
    private function asegurarRegistros(Asistencia $asistencia): void
    {
        if ($asistencia->registros()->exists()) {
            return;
        }

        $json = $asistencia->hijos_asistencia;
        if (! is_array($json) || $json === []) {
            return;
        }

        $padre = Padre::withTrashed()->where('uuid_short', $asistencia->uuid_short)->first();
        $hijos = $padre ? $padre->hijos()->withTrashed()->get()->keyBy('nombre') : collect();

        foreach ($json as $nombre => $datos) {
            if (str_starts_with((string) $nombre, 'url_firma_') || ! is_array($datos)) {
                continue;
            }

            $estado = in_array($datos['status'] ?? null, ['asistio', 'no_asistio'], true)
                ? $datos['status']
                : 'no_asistio';

            $hijo = $hijos->get(trim((string) $nombre));

            AsistenciaHijo::create([
                'asistencia_id' => $asistencia->id,
                'servicio_id' => $asistencia->servicio_id,
                'hijo_id' => $hijo?->id,
                'nombre' => trim((string) $nombre),
                'estado' => $estado,
                'ficha' => $this->normalizarFicha($datos['ficha'] ?? null),
                'entrada_at' => $estado === 'asistio' ? $asistencia->entrada_at : null,
                'salida_at' => $estado === 'asistio' ? $asistencia->salida_at : null,
            ]);
        }
    }

    /**
     * @return array{url: string, public_id: string}
     */
    private function subirFirma(string $dataUrl, string $uuid): array
    {
        $publicId = 'sig_'.$uuid.'_'.Str::uuid();

        $upload = Cloudinary::uploadApi()->upload($dataUrl, [
            'folder' => 'asistencias/firmas',
            'public_id' => $publicId,
            'resource_type' => 'image',
        ]);

        if (empty($upload['secure_url'])) {
            throw new Exception('Cloudinary no devolvió una URL para la firma.');
        }

        return [
            'url' => $upload['secure_url'],
            'public_id' => $upload['public_id'] ?? $publicId,
        ];
    }

    /**
     * @param  array{url: string, public_id: string}|null  $firma
     */
    private function borrarFirma(?array $firma): void
    {
        if (! $firma) {
            return;
        }

        app(CloudinaryAssetCleaner::class)->delete($firma['public_id'], $firma['url']);
    }

    private function registrarCambio(
        Asistencia $asistencia,
        string $accion,
        array $antes,
        array $despues,
        ?string $motivo = null,
        ?int $registroId = null,
    ): void {
        AsistenciaCambio::create([
            'asistencia_id' => $asistencia->id,
            'asistencia_hijo_id' => $registroId,
            'user_id' => auth()->id(),
            'accion' => $accion,
            'datos_anteriores' => $antes ?: null,
            'datos_nuevos' => $despues ?: null,
            'motivo' => $motivo,
        ]);
    }
}
