<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    use HasFactory;
    protected $fillable = [
        'descripcion_servicio',
        'horario_servicio',
        'fecha_servicio',
        'activo',
        // Otros campos que puedas tener
    ];


}
