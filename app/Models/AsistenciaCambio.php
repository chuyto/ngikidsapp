<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaCambio extends Model
{
    use HasFactory;

    protected $table = 'asistencia_cambios';

    protected $fillable = [
        'asistencia_id',
        'asistencia_hijo_id',
        'user_id',
        'accion',
        'datos_anteriores',
        'datos_nuevos',
        'motivo',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
    ];

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }

    public function registro(): BelongsTo
    {
        return $this->belongsTo(AsistenciaHijo::class, 'asistencia_hijo_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
