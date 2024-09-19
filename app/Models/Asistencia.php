<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'padre_uuid',
        'numero_ficha',
        'hora_entrada',
        'hora_salida',
        // Otros campos que puedas tener
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

    // Puedes agregar otros métodos o relaciones si es necesario
}
