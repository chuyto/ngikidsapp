<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use App\Models\Supervisor;
use App\Services\ReportePdfCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicioController extends Controller
{
    /**
     * Lista todos los servicios con sus supervisores cargados.
     */
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page', 15), [15, 30, 50], true) ? (int) $request->input('per_page', 15) : 15;
        $sort = in_array($request->input('sort'), ['recientes', 'antiguos', 'descripcion'], true) ? $request->input('sort') : 'recientes';
        $servicios = Servicio::with('supervisores')
            ->when($request->filled('search'), fn ($query) => $query->where('descripcion_servicio', 'like', '%'.$request->search.'%'))
            ->when($sort === 'recientes', fn ($query) => $query->orderByDesc('fecha_servicio'))
            ->when($sort === 'antiguos', fn ($query) => $query->orderBy('fecha_servicio'))
            ->when($sort === 'descripcion', fn ($query) => $query->orderBy('descripcion_servicio'))
            ->paginate($perPage)->withQueryString();
        $supervisores = Supervisor::where('activo', true)->orderBy('nombre')->get();

        return view('servicios.index', compact('servicios', 'supervisores'));
    }

    /**
     * Muestra el formulario de creación.
     */
    public function create()
    {
        $supervisores = Supervisor::where('activo', true)->orderBy('nombre')->get();

        return view('servicios.create', compact('supervisores'));
    }

    /**
     * Guarda el nuevo servicio y asocia los supervisores.
     */
    public function store(Request $request)
    {
        $request->validate([
            'descripcion_servicio' => 'required|string|max:255',
            'horario_servicio' => 'required|string',
            'fecha_servicio' => 'required|date',
            'activo' => 'required|boolean',
            'supervisores' => 'nullable|array',
            'supervisores.*' => 'exists:supervisors,id',
        ]);

        try {
            DB::beginTransaction();

            // Si el servicio se marca como activo, desactivamos cualquier otro servicio previo
            if ($request->activo) {
                Servicio::where('activo', true)->update(['activo' => false]);
            }

            // Crear el servicio
            $servicio = Servicio::create([
                'descripcion_servicio' => $request->descripcion_servicio,
                'horario_servicio' => $request->horario_servicio,
                'fecha_servicio' => $request->fecha_servicio,
                'activo' => $request->activo,
            ]);

            // Asociar supervisores mediante la relación belongsToMany
            if ($request->has('supervisores')) {
                $servicio->supervisores()->attach($request->supervisores);
            }

            DB::commit();

            return redirect()->route('servicios.index')->with('success', 'Servicio creado y supervisores asignados correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error guardando servicio: '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'No fue posible guardar el servicio. Inténtalo nuevamente.');
        }
    }

    /**
     * Muestra el formulario de edición con los datos cargados.
     */
    public function edit(Servicio $servicio)
    {
        $supervisores = Supervisor::where('activo', true)->orderBy('nombre')->get();

        // IDs de supervisores ya vinculados para que el multiselect los marque como seleccionados
        $supervisoresAsignados = $servicio->supervisores->pluck('id')->toArray();

        return view('servicios.edit', compact('servicio', 'supervisores', 'supervisoresAsignados'));
    }

    /**
     * Actualiza el servicio y sincroniza (agrega/quita) los supervisores.
     */
    public function update(Request $request, Servicio $servicio)
    {
        $request->validate([
            'descripcion_servicio' => 'required|string|max:255',
            'horario_servicio' => 'required|string',
            'fecha_servicio' => 'required|date',
            'activo' => 'required|boolean',
            'supervisores' => 'nullable|array',
            'supervisores.*' => 'exists:supervisors,id',
        ]);

        try {
            DB::beginTransaction();

            // Si este servicio se está activando, desactivamos los demás
            if ($request->activo) {
                Servicio::where('id', '!=', $servicio->id)->update(['activo' => false]);
            }

            // Actualizar datos del servicio
            $servicio->update([
                'descripcion_servicio' => $request->descripcion_servicio,
                'horario_servicio' => $request->horario_servicio,
                'fecha_servicio' => $request->fecha_servicio,
                'activo' => $request->activo,
            ]);

            // Sync es vital: borra los que no están en el array y agrega los nuevos
            $servicio->supervisores()->sync($request->supervisores ?? []);

            DB::commit();

            // El PDF incluye descripción, fecha y supervisores del servicio.
            app(ReportePdfCache::class)->flush();

            return redirect()->route('servicios.index')->with('success', 'Servicio actualizado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error actualizando servicio '.$servicio->id.': '.$e->getMessage());

            return redirect()->back()->withInput()->with('error', 'No fue posible actualizar el servicio. Inténtalo nuevamente.');
        }
    }

    /**
     * Activa un servicio específico desde la lista (index).
     */
    public function activar(Servicio $servicio)
    {
        try {
            DB::transaction(function () use ($servicio) {
                Servicio::where('activo', true)->update(['activo' => false]);
                $servicio->update(['activo' => true]);
            });

            return redirect()->back()->with('success', 'El servicio "'.$servicio->descripcion_servicio.'" ahora es el servicio activo.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se pudo activar el servicio.');
        }
    }

    /**
     * Elimina el servicio.
     */
    public function destroy(Servicio $servicio)
    {
        try {
            // Laravel se encarga de la tabla pivote si definiste cascade en la migración,
            // si no, es buena práctica hacer: $servicio->supervisores()->detach();
            $servicio->delete();
            app(ReportePdfCache::class)->flush();

            return redirect()->route('servicios.index')->with('success', 'Servicio eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'No se puede eliminar el servicio porque tiene datos relacionados.');
        }
    }
}
