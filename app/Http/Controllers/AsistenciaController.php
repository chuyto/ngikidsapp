<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use Illuminate\Http\Request;
use App\Models\Padre;
use App\Models\Servicio;
class AsistenciaController extends Controller
{
    public function create()
{
    $servicios = Servicio::where('activo', true)->get(); // Obtener solo servicios activos
    return view('asistencias.create', compact('servicios'));
}


public function store(Request $request)
{
    // Validar los datos, omitiendo el servicio_id
    $validated = $request->validate([
        'uuid_short' => 'required|exists:padres,uuid_short',
        'numero_ficha' => 'nullable|string|max:255',
        'hijos_asistencia' => 'required|array',
        'hijos_asistencia.*' => 'required|in:asistio,no_asistio',
    ]);

    // Obtener el primer servicio activo
    $servicio = Servicio::where('activo', true)->first();

    // Verificar si hay un servicio activo
    if (!$servicio) {
        return response()->json(['error' => 'No hay servicios activos disponibles.'], 422);
    }

    // Verificar si ya hay una asistencia para hoy
    $asistencia = Asistencia::where('uuid_short', $request->uuid_short)
        ->where('servicio_id', $servicio->id)  // Usar el servicio activo
        ->whereDate('hora_entrada', now()->toDateString())
        ->first();

    if ($asistencia) {
        // Si ya hay una asistencia de entrada, registrar la salida
        $asistencia->hora_salida = now();
        $asistencia->hijos_asistencia = json_encode($request->hijos_asistencia);
        $asistencia->save();

        return response()->json(['success' => 'Salida registrada correctamente.']);
    } else {
        // Si no hay una entrada, registrar la entrada
        Asistencia::create([
            'uuid_short' => $request->uuid_short,
            'numero_ficha' => $request->numero_ficha,
            'servicio_id' => $servicio->id,  // Guardar servicio_id
            'hora_entrada' => now(),
            'hijos_asistencia' => json_encode($request->hijos_asistencia),
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
            $hijos_asistencia = $asistencia ? json_decode($asistencia->hijos_asistencia, true) : [];

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
                    'hijos_asistencia' => $hijos_asistencia,  // Asistencias de los hijos en JSON
                ]
            ]);
        } else {
            return response()->json(['padre' => null]);
        }
    }
}
