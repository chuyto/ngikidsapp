<?php

namespace App\Http\Controllers;

use App\Models\Padre;
use App\Models\Hijo;
use Illuminate\Http\Request;

class PadreController extends Controller
{
    public function index()
    {
        $padres = Padre::with('hijos')->get();
        return view('padres.index', compact('padres'));
    }

    public function create()
    {
        return view('padres.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'red' => 'nullable|string|max:255',
            'telefono' => 'required|string|max:15',
            'foto_padre' => 'nullable|image',
            'hijos.*.nombre' => 'required|string|max:255',
            'hijos.*.edad' => 'required|integer',
        ]);

        $padre = new Padre();
        $padre->fill($request->only('nombre', 'red', 'telefono'));

        if ($request->hasFile('foto_padre')) {
            $padre->foto_padre = $request->file('foto_padre')->store('fotos_padres', 'public');
        }

        $padre->save();

        // Guardar los hijos
        foreach ($request->hijos as $hijo) {
            $padre->hijos()->create($hijo);
        }

        return redirect()->route('padres.index');
    }




    public function show($id)
    {
        $padre = Padre::with('hijos')->findOrFail($id);
        return view('padres.show', compact('padre'));
    }

    public function edit($id)
    {
        $padre = Padre::with('hijos')->findOrFail($id);
        return view('padres.edit', compact('padre'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'red' => 'nullable|string|max:255',
            'telefono' => 'required|string|max:15',
            'foto_padre' => 'nullable|image',
            'hijos.*.nombre' => 'required|string|max:255',
            'hijos.*.edad' => 'required|integer',
        ]);

        // Actualizar padre
        $padre = Padre::findOrFail($id);
        $padre->nombre = $request->nombre;
        $padre->red = $request->red;
        $padre->telefono = $request->telefono;

        if ($request->hasFile('foto_padre')) {
            $padre->foto_padre = $request->file('foto_padre')->store('fotos_padres', 'public');
        }

        $padre->save();

        // Actualizar hijos
        $padre->hijos()->delete(); // Elimina hijos anteriores
        foreach ($request->hijos as $hijo) {
            $padre->hijos()->create($hijo);
        }

        return redirect()->route('padres.index');
    }

    public function destroy($id)
    {
        $padre = Padre::findOrFail($id);
        $padre->delete();
        return redirect()->route('padres.index');
    }
}
