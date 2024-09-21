<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use Illuminate\Http\Request;
use App\Models\Padre;

class AsistenciaController extends Controller
{
    public function create()
    {
        return view('asistencias.create');
    }

    public function store(Request $request)
    {
        // Validar los datos
        $validated = $request->validate([
            'uuid_short' => 'required|exists:padres,uuid_short',
            'numero_ficha' => 'nullable|string|max:255',
        ]);


        // Verificar si ya hay una asistencia para hoy
        $asistencia = Asistencia::where('uuid_short', $request->uuid_short)
            ->whereDate('hora_entrada', now()->toDateString())
            ->first();

        if ($asistencia) {
            // Si ya hay una asistencia de entrada, registrar la salida
            $asistencia->hora_salida = now();
            $asistencia->save();

            return response()->json(['success' => 'Salida registrada correctamente.']);
        } else {
            // Si no hay una entrada, registrar la entrada
            Asistencia::create([
                'uuid_short' => $request->uuid_short,
                'numero_ficha' => $request->numero_ficha,
                'hora_entrada' => now(),
            ]);

            return response()->json(['success' => 'Entrada registrada correctamente.']);
        }
    }

    public function show(Request $request)
    {
        $padre = Padre::with('hijos')->where('uuid_short', $request->uuid_short)->first();

        if (!$padre) {
            return redirect()->route('asistencias.create')->with('error', 'No se encontró un padre con ese UUID.');
        }

        return view('asistencias.create', compact('padre'));
    }

    public function search(Request $request)
    {
        $padre = Padre::where('uuid_short', $request->uuid_short)->with('hijos')->first();


        if ($padre) {
            // Verificar si ya tiene una entrada registrada sin salida
            $asistencia = Asistencia::where('uuid_short', $padre->uuid_short)
                ->whereDate('hora_entrada', now()->toDateString())
                ->first();

            $entrada_registrada = $asistencia ? true : false;
            $hora_entrada = $asistencia ? $asistencia->hora_entrada : null;
            $hora_salida = $asistencia ? $asistencia->hora_salida : null;

            return response()->json([
                'padre' => [
                    'nombre' => $padre->nombre,
                    'red' => $padre->red,
                    'telefono' => $padre->telefono,
                    'foto_padre' => $padre->foto_padre,
                    'uuid_short' => $padre->uuid_short,
                    'hijos' => $padre->hijos,
                    'hora_entrada' => $hora_entrada,
                    'hora_salida' => $hora_salida,
                ]
            ]);
        } else {
            return response()->json(['padre' => null]);
        }
    }
}
