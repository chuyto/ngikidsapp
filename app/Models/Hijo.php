<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hijo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'edad',
        'fecha_nacimiento',
        'padre_id',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'deleted_with_padre' => 'boolean',
    ];

    public function padre()
    {
        return $this->belongsTo(Padre::class)->withTrashed();
    }

    public function asistenciaRegistros()
    {
        return $this->hasMany(AsistenciaHijo::class);
    }

    /**
     * La edad se calcula desde la fecha de nacimiento para que no quede
     * congelada hasta el siguiente guardado del registro.
     */
    public function getEdadAttribute(): ?int
    {
        if ($this->fecha_nacimiento) {
            return $this->fecha_nacimiento->age;
        }

        return isset($this->attributes['edad']) ? (int) $this->attributes['edad'] : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($hijo) {
            if ($hijo->fecha_nacimiento) {
                $hijo->edad = \Carbon\Carbon::parse($hijo->fecha_nacimiento)->age;
            }
        });

        static::restoring(function ($hijo) {
            $hijo->deleted_with_padre = false;
        });
    }
}
