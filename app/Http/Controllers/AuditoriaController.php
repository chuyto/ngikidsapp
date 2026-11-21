<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AuditoriaController extends Controller
{
    /**
     * Módulos visibles en el filtro. El valor es el prefijo de la acción
     * guardada en audit_logs (por ejemplo, "padre." o "sesion.").
     */
    private const MODULOS = [
        'padre' => 'Familias',
        'hijo' => 'Niños',
        'asistencia' => 'Asistencias',
        'supervisor' => 'Supervisores',
        'usuario' => 'Usuarios',
        'sesion' => 'Sesiones',
        'reporte' => 'Reportes',
        'auditoria' => 'Auditoría',
    ];

    public function index(Request $request)
    {
        $this->validarFiltros($request);

        $logs = $this->consulta($request)
            ->paginate((int) $request->input('per_page', 50))
            ->withQueryString();

        $acciones = AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $usuarios = User::query()
            ->whereIn('id', AuditLog::query()->select('user_id')->whereNotNull('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $modulos = self::MODULOS;

        return view('auditoria.index', compact('logs', 'acciones', 'usuarios', 'modulos'));
    }

    /**
     * Exporta la bitácora filtrada. La propia exportación deja rastro.
     */
    public function exportar(Request $request)
    {
        $this->validarFiltros($request);

        AuditLogger::log(
            'auditoria.exportada',
            null,
            'Exportación CSV de la bitácora de auditoría',
            [],
            ['filtros' => $request->only(['desde', 'hasta', 'user_id', 'accion', 'modulo', 'buscar'])],
        );

        $nombre = 'Auditoria_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($request) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, [
                'Fecha',
                'Usuario',
                'Correo',
                'Código',
                'Acción',
                'Descripción',
                'Entidad',
                'ID entidad',
                'IP',
                'Valores anteriores',
                'Valores nuevos',
            ]);

            $this->consulta($request)->chunk(500, function ($logs) use ($salida) {
                foreach ($logs as $log) {
                    fputcsv($salida, [
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->user?->name ?? 'Sistema',
                        $log->user?->email ?? '',
                        $log->action,
                        $log->etiqueta(),
                        $log->description,
                        $log->entidad(),
                        $log->auditable_id,
                        $log->ip,
                        $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
                        $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '',
                    ]);
                }
            });

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function consulta(Request $request): Builder
    {
        return AuditLog::query()
            ->with('user')
            ->when($request->filled('desde'), fn (Builder $query) => $query
                ->where('created_at', '>=', Carbon::parse($request->input('desde'))->startOfDay()))
            ->when($request->filled('hasta'), fn (Builder $query) => $query
                ->where('created_at', '<=', Carbon::parse($request->input('hasta'))->endOfDay()))
            ->when($request->filled('user_id'), fn (Builder $query) => $query
                ->where('user_id', $request->integer('user_id')))
            ->when($request->filled('accion'), fn (Builder $query) => $query
                ->where('action', $request->input('accion')))
            ->when($request->filled('modulo'), fn (Builder $query) => $query
                ->where('action', 'like', $request->input('modulo').'.%'))
            ->when($request->filled('buscar'), function (Builder $query) use ($request) {
                $termino = '%'.$request->input('buscar').'%';

                $query->where(function (Builder $inner) use ($termino) {
                    $inner->where('action', 'like', $termino)
                        ->orWhere('description', 'like', $termino)
                        ->orWhere('ip', 'like', $termino)
                        ->orWhereHas('user', fn (Builder $user) => $user
                            ->where('name', 'like', $termino)
                            ->orWhere('email', 'like', $termino));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    private function validarFiltros(Request $request): void
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'accion' => ['nullable', 'string', 'max:80'],
            'modulo' => ['nullable', Rule::in(array_keys(self::MODULOS))],
            'buscar' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'in:25,50,100'],
        ]);
    }
}
