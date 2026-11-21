<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaHijo extends Model
{
    use HasFactory;

    protected $table = 'asistencia_hijos';

    protected $fillable = [
        'asistencia_id',
        'servicio_id',
        'hijo_id',
        'nombre',
        'estado',
        'ficha',
        'entrada_at',
        'salida_at',
        'registrado_por_id',
        'salida_registrada_por_id',
        'corregido_por_id',
        'motivo_correccion',
    ];

    protected $casts = [
        'entrada_at' => 'datetime',
        'salida_at' => 'datetime',
    ];

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }

    public function hijo(): BelongsTo
    {
        return $this->belongsTo(Hijo::class)->withTrashed();
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function salidaRegistradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salida_registrada_por_id');
    }

    public function corregidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corregido_por_id');
    }

    public function estaAsistiendo(): bool
    {
        return $this->estado === 'asistio';
    }
}
