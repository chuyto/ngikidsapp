<?php

namespace App\Http\Controllers;

use App\Models\Supervisor;
use App\Services\AuditLogger;
use App\Services\ReportePdfCache;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupervisorController extends Controller
{
    // Muestra la lista y el formulario
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page', 15), [15, 30, 50], true) ? (int) $request->input('per_page', 15) : 15;
        $estado = in_array($request->input('estado'), ['activos', 'inactivos'], true) ? $request->input('estado') : null;

        $supervisores = Supervisor::query()
            ->when($request->filled('search'), fn ($query) => $query->where('nombre', 'like', '%'.$request->search.'%'))
            ->when($estado === 'activos', fn ($query) => $query->where('activo', true))
            ->when($estado === 'inactivos', fn ($query) => $query->where('activo', false))
            ->orderByDesc('activo')
            ->orderBy('nombre')
            ->paginate($perPage)
            ->withQueryString();

        return view('supervisores.index', compact('supervisores', 'estado'));
    }

    // Guarda el nombre en la base de datos
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255|unique:supervisors,nombre',
        ]);

        $supervisor = Supervisor::create([
            'nombre' => $data['nombre'],
            'activo' => true,
        ]);

        AuditLogger::log('supervisor.creado', $supervisor, 'Alta de supervisor '.$supervisor->nombre);

        return redirect()->back()->with('success', 'Supervisor agregado.');
    }

    public function update(Request $request, Supervisor $supervisor)
    {
        $data = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('supervisors', 'nombre')->ignore($supervisor->id),
            ],
        ]);

        $antes = ['nombre' => $supervisor->nombre];
        $supervisor->update($data);
        app(ReportePdfCache::class)->flush();

        AuditLogger::log('supervisor.actualizado', $supervisor, 'Actualización de supervisor', $antes, ['nombre' => $supervisor->nombre]);

        return redirect()->route('supervisores.index')->with('success', 'Supervisor actualizado correctamente.');
    }

    /**
     * Desactiva/reactiva sin borrar: conserva el historial de servicios.
     */
    public function toggle(Supervisor $supervisor)
    {
        $supervisor->update(['activo' => ! $supervisor->activo]);

        AuditLogger::log(
            $supervisor->activo ? 'supervisor.activado' : 'supervisor.desactivado',
            $supervisor,
            ($supervisor->activo ? 'Activación' : 'Desactivación').' del supervisor '.$supervisor->nombre,
        );

        return back()->with('success', $supervisor->activo
            ? 'Supervisor reactivado. Ya puede asignarse a servicios.'
            : 'Supervisor desactivado. Su historial se conserva.');
    }

    // Por si quieres borrar alguno
    public function destroy(Supervisor $supervisor)
    {
        AuditLogger::log(
            'supervisor.eliminado',
            $supervisor,
            'Eliminación del supervisor '.$supervisor->nombre,
            ['nombre' => $supervisor->nombre, 'activo' => $supervisor->activo],
        );

        $supervisor->delete();
        app(ReportePdfCache::class)->flush();

        return redirect()->back()->with('success', 'Supervisor eliminado.');
    }
}
