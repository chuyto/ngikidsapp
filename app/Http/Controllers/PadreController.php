<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateWalletPass;
use App\Jobs\RevokeWalletPass;
use App\Models\Asistencia;
use App\Models\Padre;
use App\Models\Red;
use App\Services\AuditLogger;
use App\Services\CloudinaryAssetCleaner;
use App\Services\DetectorDuplicados;
use App\Services\ReportePdfCache;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PadreController extends Controller
{
    public function index(Request $request)
    {
        $searchTerm = $request->input('search');
        $perPage = in_array((int) $request->input('per_page', 15), [15, 30, 50], true) ? (int) $request->input('per_page', 15) : 15;
        $sort = in_array($request->input('sort'), ['nombre', 'recientes', 'antiguos'], true) ? $request->input('sort') : 'recientes';
        $redes = Red::all();
        $papelera = $request->boolean('papelera');

        $padres = Padre::with(['hijos' => fn ($q) => $papelera ? $q->withTrashed() : $q, 'red'])
            ->when($papelera, fn ($query) => $query->onlyTrashed())
            ->when($searchTerm, function ($query, $searchTerm) {
                // Las condiciones OR deben ir agrupadas para que no escapen
                // del filtro de papelera ni del scope de SoftDeletes.
                return $query->where(function ($inner) use ($searchTerm) {
                    $inner->where('nombre', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('telefono', 'LIKE', "%{$searchTerm}%")
                        ->orWhere('uuid_short', 'LIKE', "%{$searchTerm}%");
                });
            })
            ->when($sort === 'nombre', fn ($query) => $query->orderBy('nombre'))
            ->when($sort === 'recientes', fn ($query) => $query->latest())
            ->when($sort === 'antiguos', fn ($query) => $query->oldest())
            ->paginate($perPage)
            ->withQueryString();

        return view('padres.index', compact('padres', 'searchTerm', 'redes', 'papelera'));
    }

    public function create()
    {
        $redes = Red::all();

        return view('padres.create', compact('redes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'parentesco' => 'required|string|max:255',
            'red_id' => 'nullable|exists:redes,id',
            'telefono' => 'required|string|max:15',
            'email' => 'nullable|email|max:255',
            'privacidad' => 'accepted',
            // 'foto_padre'  => 'nullable|image|max:2048', // COMENTADO
            'hijos' => 'required|array|min:1',
            'hijos.*.nombre' => 'required|string|max:255',
            'hijos.*.fecha_nacimiento' => 'required|date|before:today',
        ], [
            'privacidad.accepted' => 'Debes aceptar el Aviso de Privacidad para completar el registro.',
        ]);

        $hijos = $this->hijosDesdeRequest($request->input('hijos', []));

        /* --- DETECTOR DE DUPLICADOS ---
           Si hay posibles coincidencias y el usuario aun no confirmo que es
           un registro diferente, se regresa con el panel de aviso (no se
           crea nada). Con confirmar_duplicado=1 se guarda de todos modos. */
        if (! $request->boolean('confirmar_duplicado')) {
            $duplicados = DetectorDuplicados::paraRegistroPadre(
                $request->only(['nombre', 'telefono', 'email']),
                $hijos
            );
            if (DetectorDuplicados::hayDuplicados($duplicados)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Posibles registros duplicados. Revísalos antes de continuar.',
                        'duplicados' => $duplicados,
                    ], 422);
                }

                return back()->withInput()->with('duplicados', $duplicados);
            }
        }

        try {
            // ÚNICA OPERACIÓN: GUARDAR DATOS TEXTUALES
            $padre = DB::transaction(function () use ($request, $hijos) {
                $padre = new Padre;
                $padre->nombre = $request->nombre;
                $padre->parentesco = $request->parentesco;
                $padre->red_id = $request->red_id;
                $padre->telefono = $request->telefono;
                $padre->email = $request->email;

                /* --- LÓGICA DE FOTO DESACTIVADA ---
                if ($request->hasFile('foto_padre')) {
                    $result = Cloudinary::uploadApi()->upload($request->file('foto_padre')->getRealPath(), [
                        'folder' => 'ngikids_padres',
                        'transformation' => ['width' => 800, 'quality' => 'auto']
                    ]);
                    $padre->foto_padre = $result['secure_url'];
                }
                */

                // Evidencia del consentimiento del Aviso de Privacidad:
                // fecha, versión del aviso, IP y agente de usuario.
                $padre->privacidad_aceptada_at = now();
                $padre->privacidad_version = config('app.privacidad_version');
                $padre->privacidad_ip = $request->ip();
                $padre->privacidad_user_agent = Str::limit((string) $request->userAgent(), 1000, '');

                $padre->save();

                $padre->hijos()->createMany(
                    collect($hijos)->map(fn (array $hijo) => [
                        'nombre' => $hijo['nombre'],
                        'fecha_nacimiento' => $hijo['fecha_nacimiento'],
                    ])->all()
                );

                return $padre;
            });

            /* --- ENVÍO DE CREDENCIALES POR CORREO ---
                           El mailable implementa ShouldQueue, por lo que se encola
                           y NO bloquea la petición (evita el 504 con SMTP lento). */
            if ($padre->email) {
                try {
                    Mail::to($padre->email)->queue(new \App\Mail\PadreRegistered($padre));
                } catch (\Exception $e) {
                    \Log::error('Error encolando correo de credenciales: '.$e->getMessage());
                }
            }

            /* --- TARJETA DE WALLET (Apple/Google) ---
               La genera WalletWallet en segundo plano; el QR lleva el uuid_short
               para que la asistencia se tome igual que con la credencial. */
            $this->encolarWallet($padre);

            AuditLogger::log(
                'padre.creado',
                $padre,
                'Alta de familia '.$padre->nombre,
                [],
                ['uuid_short' => $padre->uuid_short, 'email' => $padre->email, 'hijos' => $padre->hijos()->count()],
            );

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => 'Padre registrado con éxito.',
                    'redirect' => route('padres.index'),
                ], 201);
            }

            return redirect()->route('padres.index')->with('success', 'Padre registrado con éxito.');

        } catch (\Exception $e) {
            \Log::error('Error crítico en store: '.$e->getMessage());

            return back()->withInput()->withErrors(['error' => 'No fue posible registrar al padre. Inténtalo nuevamente.']);
        }
    }

    public function show($id)
    {
        $padre = Padre::with([
            'hijos',
            'red',
            'asistencias' => fn ($query) => $query->with(['servicio', 'registros'])->latest('entrada_at')->latest('id'),
        ])->findOrFail($id);

        return view('padres.show', compact('padre'));
    }

    public function wallet(Padre $padre)
    {
        if (! config('services.walletwallet.key')) {
            return back()->with('error', 'Wallet no está configurado en Render. Agrega WALLETWALLET_API_KEY y vuelve a desplegar.');
        }

        GenerateWalletPass::dispatch($padre);

        return redirect()->route('padres.show', ['padre' => $padre, 'wallet_pending' => 1])
            ->with('success', 'La tarjeta Wallet está en proceso. Esta página se actualizará automáticamente.');
    }

    /**
     * Revoca la credencial digital y limpia el serial local para que no
     * pueda seguir usándose. La familia puede regenerarla después.
     */
    public function walletRevocar(Padre $padre)
    {
        $this->revocarWallet($padre);
        AuditLogger::log('padre.wallet_revocado', $padre, 'Revocación manual de la tarjeta Wallet');

        return back()->with('success', 'La tarjeta Wallet fue revocada. Puedes generar una nueva cuando quieras.');
    }

    /**
     * Exportación de datos personales (derecho de Acceso/Portabilidad).
     * Genera un JSON con la familia, sus hijos y su historial.
     */
    public function exportar(Padre $padre)
    {
        $padre->load([
            'red',
            'hijos' => fn ($query) => $query->withTrashed(),
            'asistencias' => fn ($query) => $query->withTrashed()->with(['servicio', 'registros'])->orderBy('entrada_at'),
        ]);

        $payload = [
            'exportado_at' => now()->toIso8601String(),
            'privacidad' => [
                'aceptada_at' => $padre->privacidad_aceptada_at?->toIso8601String(),
                'version' => $padre->privacidad_version,
                'ip' => $padre->privacidad_ip,
                'revocada_at' => $padre->privacidad_revocada_at?->toIso8601String(),
            ],
            'responsable' => [
                'nombre' => $padre->nombre,
                'parentesco' => $padre->parentesco,
                'telefono' => $padre->telefono,
                'email' => $padre->email,
                'credencial' => $padre->uuid_short,
                'red' => $padre->red?->descripcion,
                'registrado_at' => $padre->created_at?->toIso8601String(),
                'eliminada_at' => $padre->deleted_at?->toIso8601String(),
            ],
            'hijos' => $padre->hijos->map(fn ($hijo) => [
                'nombre' => $hijo->nombre,
                'fecha_nacimiento' => $hijo->fecha_nacimiento?->toDateString(),
                'edad' => $hijo->edad,
                'eliminado_at' => $hijo->deleted_at?->toIso8601String(),
            ])->values(),
            'asistencias' => $padre->asistencias->map(fn (Asistencia $asistencia) => [
                'folio' => $asistencia->uuid_short,
                'servicio' => $asistencia->servicio?->descripcion_servicio,
                'fecha_servicio' => $asistencia->servicio?->fecha_servicio,
                'entrada_at' => $asistencia->entrada_at?->toIso8601String(),
                'salida_at' => $asistencia->salida_at?->toIso8601String(),
                'anulada_at' => $asistencia->deleted_at?->toIso8601String(),
                'motivo_anulacion' => $asistencia->motivo_anulacion,
                'registros' => $asistencia->registrosParaReporte()->map(fn ($registro) => [
                    'nombre' => $registro['nombre'],
                    'ficha' => $registro['ficha'],
                    'estado' => $registro['estado'],
                    'entrada_at' => $registro['entrada_at']?->toIso8601String(),
                    'salida_at' => $registro['salida_at']?->toIso8601String(),
                ])->values(),
            ])->values(),
        ];

        AuditLogger::log('padre.exportado', $padre, 'Exportación ARCO de datos personales');

        return response()->json(
            $payload,
            200,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="datos_'.$padre->uuid_short.'.json"',
            ],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
        );
    }

    /**
     * Revoca el consentimiento y envía el expediente a papelera. Se conserva
     * para auditoría hasta que se solicite la eliminación definitiva.
     */
    public function revocarPrivacidad(Request $request, Padre $padre)
    {
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($padre, $validated) {
            $padre->update(['privacidad_revocada_at' => now()]);
            $this->revocarWallet($padre);

            AuditLogger::log(
                'padre.privacidad_revocada',
                $padre,
                'Revocación del consentimiento: '.$validated['motivo'],
            );

            $padre->delete();
        });

        // El PDF cacheado contiene datos de la familia que acaba de revocar.
        app(ReportePdfCache::class)->flush();

        return redirect()->route('padres.index')
            ->with('success', 'Consentimiento revocado y expediente enviado a la papelera.');
    }

    public function edit($id)
    {
        $padre = Padre::with('hijos')->findOrFail($id);
        $redes = Red::all();

        return view('padres.edit', compact('padre', 'redes'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'parentesco' => 'required|string|max:255',
            'red_id' => 'nullable|exists:redes,id',
            'telefono' => 'required|string|max:15',
            'email' => 'nullable|email|max:255',
            'hijos' => 'required|array|min:1',
            'hijos.*.id' => 'nullable|integer',
            'hijos.*.nombre' => 'required|string|max:255',
            'hijos.*.fecha_nacimiento' => 'required|date|before:today',
        ]);

        $hijos = $this->hijosDesdeRequest($request->input('hijos', []));

        /* --- DETECTOR DE DUPLICADOS (ignora su propio registro) --- */
        if (! $request->boolean('confirmar_duplicado')) {
            $duplicados = DetectorDuplicados::paraRegistroPadre(
                $request->only(['nombre', 'telefono', 'email']),
                $hijos,
                (int) $id
            );
            if (DetectorDuplicados::hayDuplicados($duplicados)) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Posibles registros duplicados. Revísalos antes de continuar.',
                        'duplicados' => $duplicados,
                    ], 422);
                }

                return back()->withInput()->with('duplicados', $duplicados);
            }
        }

        try {
            $antes = Padre::findOrFail($id)->only(['nombre', 'parentesco', 'red_id', 'telefono', 'email']);

            $padre = DB::transaction(function () use ($request, $id, $hijos) {
                $padre = Padre::findOrFail($id);
                $padre->nombre = $request->nombre;
                $padre->parentesco = $request->parentesco;
                $padre->red_id = $request->red_id;
                $padre->telefono = $request->telefono;
                $padre->email = $request->email;

                $padre->save();

                // Actualizar en sitio los hijos existentes conserva sus IDs y
                // el historial de asistencias. Solo se eliminan los que el
                // usuario quitó del formulario.
                $idsConservados = collect($hijos)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
                $padre->hijos()->whereNotIn('id', $idsConservados)->delete();

                foreach ($hijos as $datos) {
                    $hijo = ! empty($datos['id']) ? $padre->hijos()->find($datos['id']) : null;
                    $valores = [
                        'nombre' => $datos['nombre'],
                        'fecha_nacimiento' => $datos['fecha_nacimiento'],
                    ];

                    if ($hijo) {
                        $hijo->update($valores);
                    } else {
                        $padre->hijos()->create($valores);
                    }
                }

                return $padre->refresh();
            });

            // El PDF del reporte incluye nombre y red de la familia.
            app(ReportePdfCache::class)->flush();

            /* --- TARJETA DE WALLET (Apple/Google) ---
               Refresca el pase existente (PUT al mismo serial); si aun no
               tiene, lo crea. Asi no depende del boton manual. */
            $this->encolarWallet($padre);

            AuditLogger::log(
                'padre.actualizado',
                $padre,
                'Actualización de la familia '.$padre->nombre,
                $antes,
                $padre->only(['nombre', 'parentesco', 'red_id', 'telefono', 'email']),
            );

            return redirect()->route('padres.index')
                ->with('success', 'Registro actualizado correctamente.');

        } catch (\Exception $e) {
            \Log::error('Error actualizando padre '.$id.': '.$e->getMessage());

            return back()->withInput()->withErrors(['error' => 'No fue posible actualizar el registro. Inténtalo nuevamente.']);
        }
    }

    /**
     * Normaliza el arreglo de hijos que llega del formulario.
     *
     * @return array<int, array{id: int|null, nombre: string, fecha_nacimiento: string}>
     */
    private function hijosDesdeRequest(array $hijos): array
    {
        return collect($hijos)
            ->filter(fn ($hijo) => is_array($hijo))
            ->map(fn (array $hijo) => [
                'id' => isset($hijo['id']) && $hijo['id'] !== '' ? (int) $hijo['id'] : null,
                'nombre' => trim((string) ($hijo['nombre'] ?? '')),
                'fecha_nacimiento' => (string) ($hijo['fecha_nacimiento'] ?? ''),
            ])
            ->filter(fn (array $hijo) => $hijo['nombre'] !== '' && $hijo['fecha_nacimiento'] !== '')
            ->values()
            ->all();
    }

    public function destroy($id)
    {
        $padre = Padre::findOrFail($id);

        DB::transaction(function () use ($padre) {
            $this->revocarWallet($padre);

            AuditLogger::log(
                'padre.enviado_papelera',
                $padre,
                'Familia enviada a la papelera',
                ['nombre' => $padre->nombre],
            );

            $padre->delete();
        });

        app(ReportePdfCache::class)->flush();

        return redirect()->route('padres.index')->with('success', 'Registro enviado a la papelera.');
    }

    public function restore($id)
    {
        $padre = Padre::withTrashed()->findOrFail($id);
        $padre->restore();
        $padre->update(['privacidad_revocada_at' => null]);
        app(ReportePdfCache::class)->flush();

        $this->encolarWallet($padre);

        AuditLogger::log('padre.restaurado', $padre, 'Familia restaurada desde la papelera');

        return back()->with('success', 'Registro restaurado correctamente.');
    }

    /**
     * Eliminación definitiva (derecho de Cancelación):
     * - revoca el pase Wallet,
     * - borra firmas y foto en Cloudinary,
     * - elimina asistencias, hijos y PDFs cacheados.
     */
    public function forceDelete($id)
    {
        $padre = Padre::withTrashed()->findOrFail($id);
        $cleaner = app(CloudinaryAssetCleaner::class);

        $asistencias = Asistencia::withTrashed()
            ->where('uuid_short', $padre->uuid_short)
            ->get();

        foreach ($asistencias as $asistencia) {
            $json = $asistencia->hijos_asistencia ?? [];
            $cleaner->delete($asistencia->firma_entrada_public_id, $json['url_firma_entrada'] ?? null);
            $cleaner->delete($asistencia->firma_salida_public_id, $json['url_firma_salida'] ?? null);
        }

        $cleaner->delete($padre->foto_padre_public_id, $padre->foto_padre);

        if ($padre->wallet_serial) {
            RevokeWalletPass::dispatch($padre->wallet_serial);
        }

        $auditoria = [
            'nombre' => $padre->nombre,
            'uuid_short' => $padre->uuid_short,
            'email' => $padre->email,
            'telefono' => $padre->telefono,
            'asistencias' => $asistencias->count(),
            'hijos' => $padre->hijos()->withTrashed()->count(),
        ];

        DB::transaction(function () use ($padre) {
            // Las firmas y registros por niño caen por las FK en cascada.
            Asistencia::withTrashed()->where('uuid_short', $padre->uuid_short)->forceDelete();
            $padre->hijos()->withTrashed()->forceDelete();
            $padre->forceDelete();
        });

        app(ReportePdfCache::class)->flush();

        AuditLogger::log(
            'padre.eliminado_definitivo',
            null,
            'Eliminación definitiva y anonimización de la familia '.$auditoria['nombre'],
            $auditoria,
        );

        return redirect()->route('padres.index', ['papelera' => 1])->with('success', 'Registro eliminado definitivamente.');
    }

    /**
     * Encola la actualización/creación de la tarjeta si Wallet está activo.
     */
    private function encolarWallet(Padre $padre, ?string $notificacion = null): void
    {
        if (! config('services.walletwallet.key')) {
            return;
        }

        try {
            GenerateWalletPass::dispatch($padre, $notificacion);
        } catch (\Throwable $e) {
            Log::error('Error encolando la tarjeta Wallet: '.$e->getMessage());
        }
    }

    /**
     * Revoca el pase remoto y limpia los datos locales de la credencial.
     */
    private function revocarWallet(Padre $padre): void
    {
        if ($padre->wallet_serial) {
            try {
                RevokeWalletPass::dispatch($padre->wallet_serial);
            } catch (\Throwable $e) {
                Log::warning('No se pudo encolar la revocación del pase Wallet: '.$e->getMessage());
            }
        }

        $padre->forceFill([
            'wallet_serial' => null,
            'wallet_share_url' => null,
            'wallet_revocado_at' => now(),
        ])->save();
    }
}
