<?php

namespace App\Http\Controllers;

use App\Models\Hijo;
use App\Models\Padre;
use Illuminate\Http\Request;

class HijoController extends Controller
{
    public function index()
    {
        $hijos = Hijo::with('padre')->get();
        return view('hijos.index', compact('hijos'));
    }

    public function create()
    {
        $padres = Padre::all();
        return view('hijos.create', compact('padres'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'padre_id' => 'required|exists:padres,id',
        ]);

        Hijo::create($request->all());
        return redirect()->route('hijos.index')->with('success', 'Hijo creado exitosamente.');
    }

    public function edit(Hijo $hijo)
    {
        $padres = Padre::all();
        return view('hijos.edit', compact('hijo', 'padres'));
    }

    public function update(Request $request, Hijo $hijo)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'padre_id' => 'required|exists:padres,id',
        ]);

        $hijo->update($request->all());
        return redirect()->route('hijos.index')->with('success', 'Hijo actualizado exitosamente.');
    }

    public function destroy(Hijo $hijo)
    {
        $hijo->delete();
        return redirect()->route('hijos.index')->with('success', 'Hijo eliminado exitosamente.');
    }
}
