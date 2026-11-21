<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\AsistenciaHijo;
use App\Models\Hijo;
use App\Models\Red;
use App\Models\Servicio;
use App\Services\AuditLogger;
use App\Services\ReportePdfCache;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'servicio_id' => ['nullable', 'integer', 'exists:servicios,id'],
            'red_id' => ['nullable', 'integer', 'exists:redes,id'],
            'hijo_id' => ['nullable', 'integer', 'exists:hijos,id'],
            'estado' => ['nullable', 'in:asistio,no_asistio'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'per_page' => ['nullable', 'integer', 'in:15,25,50'],
            'formato' => ['nullable', 'in:pdf,csv'],
        ]);

        $servicios = Servicio::orderByDesc('fecha_servicio')->get();
        $redes = Red::orderBy('descripcion')->get();

        $servicio = $request->filled('servicio_id')
            ? Servicio::with('supervisores')->find($request->integer('servicio_id'))
            : null;

        // Solo mostramos niños que tengan algún registro en el servicio
        // elegido; evita un selector con toda la base completa.
        $hijos = $servicio
            ? Hijo::query()
                ->whereHas('padre.asistencias', fn ($query) => $query->where('servicio_id', $servicio->id))
                ->orderBy('nombre')
                ->get()
            : collect();

        $asistencias = collect();
        $totalNinos = 0;

        if ($servicio) {
            $query = $this->consultaFiltrada($request, $servicio);

            if ($request->input('formato') === 'csv') {
                return $this->exportarCsv($query->get(), $servicio, $request);
            }

            if ($request->boolean('exportar') || $request->input('formato') === 'pdf') {
                return $this->exportarPdf($request, $query->get(), $servicio);
            }

            $asistencias = $query
                ->paginate((int) $request->input('per_page', 25))
                ->withQueryString();

            $totalNinos = $this->contarNinos($request, $servicio);
        }

        return view('reportes.index', compact('servicios', 'redes', 'hijos', 'asistencias', 'totalNinos'));
    }

    /**
     * Ruta directa de exportación PDF (mantiene compatibilidad con enlaces
     * viejos que apuntaban a /reportes/pdf).
     */
    public function pdf(Request $request)
    {
        $request->merge(['formato' => 'pdf']);

        return $this->index($request);
    }

    /**
     * Ruta directa de exportación CSV.
     */
    public function csv(Request $request)
    {
        $request->merge(['formato' => 'csv']);

        return $this->index($request);
    }

    /**
     * Construye la consulta con todos los filtros activos. Los registros
     * por niño se cargan con las mismas restricciones para que la vista,
     * el PDF y el CSV muestren exactamente lo mismo.
     */
    private function consultaFiltrada(Request $request, Servicio $servicio)
    {
        $query = Asistencia::query()
            ->where('servicio_id', $servicio->id)
            ->with([
                'padre.red',
                'servicio',
                'registros',
            ]);

        if ($request->filled('red_id')) {
            $query->whereHas('padre', fn ($padre) => $padre->where('red_id', $request->integer('red_id')));
        }

        if ($request->filled('hijo_id')) {
            $query->whereHas('registros', fn ($registro) => $registro->where('hijo_id', $request->integer('hijo_id')));
        }

        if ($request->filled('estado')) {
            $query->whereHas('registros', fn ($registro) => $registro->where('estado', $request->input('estado')));
        }

        if ($request->filled('desde')) {
            $query->where('entrada_at', '>=', Carbon::parse($request->input('desde'))->startOfDay());
        }

        if ($request->filled('hasta')) {
            $query->where('entrada_at', '<=', Carbon::parse($request->input('hasta'))->endOfDay());
        }

        return $query->orderByDesc('entrada_at')->orderByDesc('id');
    }

    private function contarNinos(Request $request, Servicio $servicio): int
    {
        if ($request->input('estado') === 'no_asistio') {
            return 0;
        }

        $query = AsistenciaHijo::query()
            ->where('estado', 'asistio')
            ->whereHas('asistencia', function ($asistencia) use ($request, $servicio) {
                $asistencia->where('servicio_id', $servicio->id);

                if ($request->filled('red_id')) {
                    $asistencia->whereHas('padre', fn ($padre) => $padre->where('red_id', $request->integer('red_id')));
                }
                if ($request->filled('desde')) {
                    $asistencia->where('entrada_at', '>=', Carbon::parse($request->input('desde'))->startOfDay());
                }
                if ($request->filled('hasta')) {
                    $asistencia->where('entrada_at', '<=', Carbon::parse($request->input('hasta'))->endOfDay());
                }
            });

        if ($request->filled('hijo_id')) {
            $query->where('hijo_id', $request->integer('hijo_id'));
        }

        return $query->count();
    }

    /**
     * Genera (o reutiliza) el PDF. El caché vive en disco y se invalida
     * cuando cambia una asistencia, una corrección o un borrado de familia.
     */
    private function exportarPdf(Request $request, $asistencias, Servicio $servicio)
    {
        $nombreLimpio = str_replace([' ', '/', '\\'], '_', $servicio->descripcion_servicio);
        $nombreArchivo = 'Reporte_'.preg_replace('/[^A-Za-z0-9\_]/', '', $nombreLimpio).'.pdf';

        $firma = implode('_', [
            optional($asistencias->max('updated_at'))->timestamp ?? '0',
            $asistencias->count(),
            $asistencias->flatMap->registros->max('updated_at')?->timestamp ?? '0',
        ]);

        $llave = 'reporte_pdf_'.$servicio->id.'_'.md5(json_encode([
            'red_id' => $request->filled('red_id') ? $request->integer('red_id') : null,
            'hijo_id' => $request->filled('hijo_id') ? $request->integer('hijo_id') : null,
            'estado' => $request->input('estado'),
            'desde' => $request->input('desde'),
            'hasta' => $request->input('hasta'),
            'firma' => $firma,
        ]));

        $cache = app(ReportePdfCache::class);
        $binario = $cache->get($llave);

        if (! $binario) {
            $binario = $this->generarPdfBinario($asistencias, $servicio);
            $cache->put($llave, $binario, [
                'servicio_id' => $servicio->id,
                'asistencias' => $asistencias->count(),
            ]);
        }

        AuditLogger::log(
            'reporte.pdf',
            null,
            'Descarga de reporte PDF: '.$servicio->descripcion_servicio,
            [],
            $this->datosReporte($request, $servicio, 'pdf', $asistencias->count()),
        );

        return response($binario, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$nombreArchivo.'"',
        ]);
    }

    /**
     * CSV por niño (no por familia), ideal para conciliaciones y Excel.
     */
    private function exportarCsv($asistencias, Servicio $servicio, Request $request)
    {
        $nombre = 'Reporte_'.preg_replace('/[^A-Za-z0-9\_]/', '', str_replace([' ', '/', '\\'], '_', $servicio->descripcion_servicio)).'.csv';
        $hijoId = $request->filled('hijo_id') ? $request->integer('hijo_id') : null;
        $estado = $request->input('estado');

        AuditLogger::log(
            'reporte.csv',
            null,
            'Descarga de reporte CSV: '.$servicio->descripcion_servicio,
            [],
            $this->datosReporte($request, $servicio, 'csv', $asistencias->count()),
        );

        return response()->streamDownload(function () use ($asistencias, $hijoId, $estado) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");

            fputcsv($salida, [
                'Servicio',
                'Fecha del servicio',
                'Padre / Responsable',
                'Credencial',
                'Red',
                'Niño',
                'Ficha',
                'Estado',
                'Entrada',
                'Salida',
            ]);

            foreach ($asistencias as $asistencia) {
                foreach ($asistencia->registrosParaReporte($hijoId, $estado) as $registro) {
                    fputcsv($salida, [
                        $asistencia->servicio?->descripcion_servicio,
                        $asistencia->servicio?->fecha_servicio
                            ? Carbon::parse($asistencia->servicio->fecha_servicio)->format('d/m/Y')
                            : '',
                        $asistencia->padre?->nombre,
                        $asistencia->uuid_short,
                        $asistencia->padre?->red?->descripcion,
                        $registro['nombre'],
                        $registro['ficha'],
                        $registro['estado'] === 'asistio' ? 'Asistió' : 'No asistió',
                        $registro['entrada_at']?->format('d/m/Y H:i'),
                        $registro['salida_at']?->format('d/m/Y H:i'),
                    ]);
                }
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Resumen de filtros para la bitácora. No se guardan nombres de niños,
     * solo identificadores, para no duplicar datos personales innecesarios.
     */
    private function datosReporte(Request $request, Servicio $servicio, string $formato, int $asistencias): array
    {
        return [
            'formato' => $formato,
            'servicio_id' => $servicio->id,
            'servicio' => $servicio->descripcion_servicio,
            'red_id' => $request->filled('red_id') ? $request->integer('red_id') : null,
            'hijo_id' => $request->filled('hijo_id') ? $request->integer('hijo_id') : null,
            'estado' => $request->input('estado'),
            'desde' => $request->input('desde'),
            'hasta' => $request->input('hasta'),
            'asistencias' => $asistencias,
        ];
    }

    /**
     * Construye el binario del PDF: descarga las firmas (versión reducida
     * para que pese poco) y las adjunta como atributos temporales, sin
     * tocar el JSON legado que se muestra en pantalla.
     */
    private function generarPdfBinario($asistencias, $servicio): string
    {
        foreach ($asistencias as $asistencia) {
            $data = $asistencia->hijos_asistencia ?? [];

            $asistencia->setAttribute(
                'firma_entrada_base64',
                $this->firmaBase64($data['url_firma_entrada'] ?? null)
            );
            $asistencia->setAttribute(
                'firma_salida_base64',
                $this->firmaBase64($data['url_firma_salida'] ?? null)
            );
        }

        return Pdf::loadView('reportes.pdf', [
            'asistencias' => $asistencias,
            'servicio' => $servicio,
        ])->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'sans-serif',
            ])->output();
    }

    private function firmaBase64(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        try {
            $contenido = $this->downloadCloudinaryImage($this->firmaReducida($url));

            return $contenido ? 'data:image/png;base64,'.base64_encode($contenido) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Pide a Cloudinary una versión liviana de la firma (las originales
     * pesan MBs y el PDF solo las muestra en miniatura).
     */
    private function firmaReducida(string $url): string
    {
        if (str_contains($url, '/upload/')) {
            return (string) preg_replace('#/upload/#', '/upload/w_500,q_auto/', $url, 1);
        }

        return $url;
    }

    /**
     * Descarga una imagen solo si es una URL https de Cloudinary
     * (protege contra SSRF), con timeout y tope de 5 MB.
     */
    private function downloadCloudinaryImage(string $url): string|false
    {
        $parts = parse_url($url);
        if (($parts['scheme'] ?? null) !== 'https'
            || ! str_ends_with(strtolower($parts['host'] ?? ''), '.cloudinary.com')) {
            return false;
        }

        $context = stream_context_create([
            'http' => ['timeout' => 5, 'follow_location' => 0],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        return @file_get_contents($url, false, $context, 0, 5 * 1024 * 1024);
    }
}
