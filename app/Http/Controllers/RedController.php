<?php

namespace App\Http\Controllers;

use App\Models\Red;
use App\Services\ReportePdfCache;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class RedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page', 15), [15, 30, 50], true) ? (int) $request->input('per_page', 15) : 15;
        $redes = Red::query()
            ->when($request->filled('search'), fn ($query) => $query->where('descripcion', 'like', '%'.$request->search.'%'))
            ->orderBy('descripcion')->paginate($perPage)->withQueryString();

        return view('redes.index', compact('redes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view('redes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validatedData = $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        Red::create($validatedData);

        return redirect()->route('redes.index'); // Redirigir a la lista de redes
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return redirect()->route('redes.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
        $rede = Red::findOrFail($id);

        return view('redes.edit', compact('rede'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $validatedData = $request->validate([
            'descripcion' => 'required|string|max:255',
        ]);

        $rede = Red::findOrFail($id);
        $rede->update($validatedData);
        app(ReportePdfCache::class)->flush();

        return redirect()->route('redes.index')->with('success', 'Red actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $rede = Red::findOrFail($id);

        try {
            $rede->delete();
            app(ReportePdfCache::class)->flush();
        } catch (QueryException $e) {
            \Log::warning('No se pudo eliminar la red '.$id.': '.$e->getMessage());

            return redirect()->route('redes.index')
                ->with('error', 'No se puede eliminar la red porque tiene familias asignadas.');
        }

        return redirect()->route('redes.index')->with('success', 'Red eliminada correctamente.');
    }
}
