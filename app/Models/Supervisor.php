<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supervisor extends Model
{
    protected $fillable = ['nombre', 'activo'];

    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'servicio_supervisor');
    }
}
