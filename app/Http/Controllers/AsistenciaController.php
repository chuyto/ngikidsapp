<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'padre_uuid' => 'required|exists:padres,uuid_short',
            'numero_ficha' => [
                'nullable', // No obligatorio por defecto
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    // Solo requerido si se está registrando una entrada
                    $asistencia = Asistencia::where('padre_uuid', $request->padre_uuid)
                        ->whereDate('hora_entrada', now()->toDateString()) // Verificar si ya tiene una entrada hoy
                        ->first();

                    if (!$asistencia && empty($value)) {
                        $fail('El campo número de ficha es obligatorio cuando se registra una entrada.');
                    }
                },
            ],
        ]);

        // Verificar si ya hay una asistencia para hoy
        $asistencia = Asistencia::where('padre_uuid', $request->padre_uuid)
            ->whereDate('hora_entrada', now()->toDateString()) // Verificar si ya tiene una entrada hoy
            ->first();

        if ($asistencia) {
            // Si ya hay una asistencia de entrada, registrar la salida
            $asistencia->hora_salida = now();
            $asistencia->save();

            return response()->json(['success' => 'Salida registrada correctamente.']);
        } else {
            // Si no hay una entrada, registrar la entrada
            Asistencia::create([
                'padre_uuid' => $request->padre_uuid,
                'numero_ficha' => $request->numero_ficha,
                'hora_entrada' => now(),
            ]);

            return response()->json(['success' => 'Entrada registrada correctamente.']);
        }
    }


    public function show(Request $request)
    {
        $padre = Padre::with('hijos')->where('uuid', $request->padre_uuid)->first();

        if (!$padre) {
            return redirect()->route('asistencias.create')->with('error', 'No se encontró un padre con ese UUID.');
        }

        return view('asistencias.create', compact('padre'));
    }

   // app/Http/Controllers/AsistenciaController.php

   public function search(Request $request)
   {
       $padre = Padre::where('uuid_short', $request->padre_uuid)->with('hijos')->first();

       if ($padre) {
           // Verificar si ya tiene una entrada registrada sin salida
           $asistencia = Asistencia::where('padre_uuid', $padre->uuid_short)
               ->whereDate('hora_entrada', now()->toDateString()) // Verificar si hay una entrada hoy
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
