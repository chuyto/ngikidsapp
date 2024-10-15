<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid_short',
        'numero_ficha',
        'hora_entrada',
        'hora_salida',
        'hijos_asistencia',
        'servicio_id'  // Agregamos el nuevo campo
    ];

    // Cast para trabajar con el campo como JSON
    protected $casts = [
        'hijos_asistencia' => 'array', // Cast para el campo JSON
    ];

    // Mutador para formatear la hora de entrada
    public function getHoraEntradaAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i:s') : null;
    }

    // Mutador para formatear la hora de salida
    public function getHoraSalidaAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('H:i:s') : null;
    }
}

