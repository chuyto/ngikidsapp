<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Red;

class RedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        $redes = Red::all();
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
        //
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

        return response()->json($rede); // Devolver JSON con el servicio actualizado
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $rede = Red::findOrFail($id);
        $rede->delete();

        return response()->json(['message' => 'Red eliminado exitosamente.'], 200);
    }
}
