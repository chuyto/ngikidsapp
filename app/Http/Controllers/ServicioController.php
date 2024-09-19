<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $servicios = Servicio::all();
        return view('servicios.index', compact('servicios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('servicios.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'descripcion_servicio' => 'required|string|max:255',
            'horario_servicio' => 'required|string|max:255',
            'fecha_servicio' => 'required|date',
            'activo' => 'required|boolean',
        ]);

        Servicio::create($validatedData);

        return redirect()->route('servicios.index'); // Redirigir a la lista de servicios
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $servicio = Servicio::findOrFail($id);
        return view('servicios.show', compact('servicio'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $servicio = Servicio::findOrFail($id);
        return view('servicios.edit', compact('servicio'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validatedData = $request->validate([
            'descripcion_servicio' => 'required|string|max:255',
            'horario_servicio' => 'required|string|max:255',
            'fecha_servicio' => 'required|date',
            'activo' => 'required|boolean',
        ]);

        $servicio = Servicio::findOrFail($id);
        $servicio->update($validatedData);

        return response()->json($servicio); // Devolver JSON con el servicio actualizado
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $servicio = Servicio::findOrFail($id);
        $servicio->delete();

        return response()->json(['message' => 'Servicio eliminado exitosamente.'], 200);
    }
}
