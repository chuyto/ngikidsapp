<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateWalletPass;
use App\Models\Asistencia;
use App\Models\AsistenciaHijo;
use App\Models\Hijo;
use App\Models\Padre;
use App\Services\AuditLogger;
use App\Services\DetectorDuplicados;
use App\Services\ReportePdfCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HijoController extends Controller
{
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page', 15), [15, 30, 50], true) ? (int) $request->input('per_page', 15) : 15;
        $sort = in_array($request->input('sort'), ['nombre', 'edad', 'recientes'], true) ? $request->input('sort') : 'nombre';
        $papelera = $request->boolean('papelera');
        $hijos = Hijo::with(['padre' => fn ($q) => $q->withTrashed()])
            ->when($papelera, fn ($query) => $query->onlyTrashed())
            ->when($request->filled('search'), fn ($query) => $query
                ->where(fn ($inner) => $inner->where('nombre', 'like', '%'.$request->search.'%')
                    ->orWhereHas('padre', fn ($padre) => $padre->where('nombre', 'like', '%'.$request->search.'%'))))
            ->when($sort === 'nombre', fn ($query) => $query->orderBy('nombre'))
            ->when($sort === 'edad', fn ($query) => $query->orderByDesc('edad'))
            ->when($sort === 'recientes', fn ($query) => $query->latest())
            ->paginate($perPage)->withQueryString();
        $padres = Padre::orderBy('nombre')->get();

        return view('hijos.index', compact('hijos', 'padres', 'papelera'));
    }

    public function create()
    {
        $padres = Padre::all();

        return view('hijos.create', compact('padres'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'padre_id' => 'required|exists:padres,id',
            'fecha_nacimiento' => 'required|date|before:today',
        ]);

        /* --- DETECTOR DE DUPLICADOS (mismo niño ya registrado) --- */
        if (! $request->boolean('confirmar_duplicado')) {
            $dups = DetectorDuplicados::buscarHijos($data['nombre'], $data['fecha_nacimiento']);
            if (count($dups) > 0) {
                return back()->withInput()->with('duplicados', ['padres' => [], 'hijos' => $dups]);
            }
        }

        $hijo = Hijo::create($data);
        $this->refrescarWallet($hijo->padre);
        AuditLogger::log('hijo.creado', $hijo, 'Alta del niño '.$hijo->nombre, [], ['padre_id' => $hijo->padre_id]);

        return redirect()->route('hijos.index')->with('success', 'Hijo creado exitosamente.');
    }

    public function edit(Hijo $hijo)
    {
        $padres = Padre::all();

        return view('hijos.edit', compact('hijo', 'padres'));
    }

    public function update(Request $request, Hijo $hijo)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'padre_id' => 'required|exists:padres,id',
            'fecha_nacimiento' => 'required|date|before:today',
        ]);

        /* --- DETECTOR DE DUPLICADOS (ignora su propio registro) --- */
        if (! $request->boolean('confirmar_duplicado')) {
            $dups = DetectorDuplicados::buscarHijos($data['nombre'], $data['fecha_nacimiento'], null, $hijo->id);
            if (count($dups) > 0) {
                return back()->withInput()->with('duplicados', ['padres' => [], 'hijos' => $dups]);
            }
        }

        $antes = $hijo->only(['nombre', 'padre_id', 'fecha_nacimiento']);
        $padreAnterior = $hijo->padre;
        $hijo->update($data);

        $this->refrescarWallet($padreAnterior);
        $this->refrescarWallet($hijo->padre);
        app(ReportePdfCache::class)->flush();
        AuditLogger::log('hijo.actualizado', $hijo, 'Actualización del niño '.$hijo->nombre, $antes, $hijo->only(['nombre', 'padre_id', 'fecha_nacimiento']));

        return redirect()->route('hijos.index')->with('success', 'Hijo actualizado exitosamente.');
    }

    public function destroy(Hijo $hijo)
    {
        $this->refrescarWallet($hijo->padre);
        AuditLogger::log('hijo.enviado_papelera', $hijo, 'Niño enviado a la papelera: '.$hijo->nombre);

        $hijo->delete();
        app(ReportePdfCache::class)->flush();

        return redirect()->route('hijos.index')->with('success', 'Registro enviado a la papelera.');
    }

    public function restore($id)
    {
        $hijo = Hijo::withTrashed()->findOrFail($id);
        $hijo->restore();
        $this->refrescarWallet($hijo->padre);
        app(ReportePdfCache::class)->flush();
        AuditLogger::log('hijo.restaurado', $hijo, 'Niño restaurado: '.$hijo->nombre);

        return back()->with('success', 'Registro restaurado correctamente.');
    }

    /**
     * Eliminación definitiva de un niño: se anonimizan sus registros de
     * asistencia (se conserva el conteo histórico sin datos personales).
     */
    public function forceDelete($id)
    {
        $hijo = Hijo::withTrashed()->findOrFail($id);
        $padre = $hijo->padre;
        $asistenciaIds = AsistenciaHijo::where('hijo_id', $hijo->id)->pluck('asistencia_id')->unique();

        DB::transaction(function () use ($hijo) {
            AsistenciaHijo::where('hijo_id', $hijo->id)->update([
                'hijo_id' => null,
                'nombre' => 'Niño eliminado #'.$hijo->id,
                'ficha' => null,
            ]);

            $hijo->forceDelete();
        });

        // El JSON legado deja de contener el nombre del niño.
        Asistencia::whereIn('id', $asistenciaIds)->get()->each(function (Asistencia $asistencia) {
            $asistencia->load('registros');
            $asistencia->sincronizarJsonLegacy();
        });

        app(ReportePdfCache::class)->flush();
        $this->refrescarWallet($padre);

        AuditLogger::log(
            'hijo.eliminado_definitivo',
            null,
            'Eliminación definitiva y anonimización del niño '.$hijo->nombre,
            ['id' => $hijo->id, 'nombre' => $hijo->nombre, 'padre_id' => $hijo->padre_id],
        );

        return redirect()->route('hijos.index', ['papelera' => 1])->with('success', 'Registro eliminado definitivamente.');
    }

    /**
     * Refresca la tarjeta Wallet del responsable si ya tiene una emitida.
     */
    private function refrescarWallet(?Padre $padre): void
    {
        if (! $padre || ! $padre->wallet_serial || ! config('services.walletwallet.key')) {
            return;
        }

        try {
            GenerateWalletPass::dispatch($padre);
        } catch (\Throwable $e) {
            Log::warning('No se pudo refrescar la tarjeta Wallet del padre '.$padre->id.': '.$e->getMessage());
        }
    }
}
