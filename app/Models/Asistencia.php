<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asistencia extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Los atributos que se pueden asignar de forma masiva.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid_short',
        'hora_entrada',
        'hora_salida',
        'hijos_asistencia', // JSON legado; la fuente nueva es asistencia_hijos.
        'servicio_id',
        'entrada_at',
        'salida_at',
        'registrado_por_id',
        'salida_registrada_por_id',
        'firma_entrada_public_id',
        'firma_salida_public_id',
        'anulada_por_id',
        'motivo_anulacion',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'hijos_asistencia' => 'array',
        'entrada_at' => 'datetime',
        'salida_at' => 'datetime',
    ];

    /**
     * Relación con el Padre.
     */
    public function padre(): BelongsTo
    {
        return $this->belongsTo(Padre::class, 'uuid_short', 'uuid_short');
    }

    /**
     * Relación con el Servicio.
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }

    /**
     * Registros individuales por niño.
     */
    public function registros(): HasMany
    {
        return $this->hasMany(AsistenciaHijo::class)->orderBy('id');
    }

    /**
     * Bitácora de cambios de esta asistencia.
     */
    public function cambios(): HasMany
    {
        return $this->hasMany(AsistenciaCambio::class)->latest('id');
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por_id');
    }

    public function salidaRegistradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salida_registrada_por_id');
    }

    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por_id');
    }

    /**
     * Hora de entrada formateada. Usa la columna nueva y cae al campo
     * legado (TIME) para registros que aún no se hayan migrado en caliente.
     */
    public function getFormatedEntradaAttribute(): string
    {
        if ($this->entrada_at) {
            return $this->entrada_at->format('h:i A');
        }

        return $this->hora_entrada
            ? \Carbon\Carbon::parse($this->hora_entrada)->format('h:i A')
            : '--:--';
    }

    public function getFormatedSalidaAttribute(): string
    {
        if ($this->salida_at) {
            return $this->salida_at->format('h:i A');
        }

        return $this->hora_salida
            ? \Carbon\Carbon::parse($this->hora_salida)->format('h:i A')
            : '--:--';
    }

    public function getFormatedFechaAttribute(): string
    {
        $fecha = $this->entrada_at ?: $this->created_at;

        return $fecha ? $fecha->format('d/m/Y') : '--/--/----';
    }

    /**
     * Mantiene el JSON legado en sincronía para reportes viejos o rollback.
     * No es la fuente de verdad, pero evita romper consumidores externos.
     */
    public function sincronizarJsonLegacy(): void
    {
        $registros = $this->relationLoaded('registros')
            ? $this->registros
            : $this->registros()->get();

        $json = [];
        foreach ($registros as $registro) {
            $json[$registro->nombre] = [
                'status' => $registro->estado,
                'ficha' => $registro->ficha,
            ];
        }

        if ($this->firma_entrada_public_id || $this->hora_entrada) {
            $json['url_firma_entrada'] = $this->hijos_asistencia['url_firma_entrada'] ?? null;
        }
        if ($this->salida_at || $this->hora_salida) {
            $json['url_firma_salida'] = $this->hijos_asistencia['url_firma_salida'] ?? null;
        }

        $this->hijos_asistencia = $json;
        $this->saveQuietly();

        // Las columnas legadas son TIME: se actualizan con SQL para no
        // depender de la conversión datetime -> time de MySQL.
        \Illuminate\Support\Facades\DB::table('asistencias')
            ->where('id', $this->id)
            ->update([
                'hora_entrada' => $this->entrada_at?->format('H:i:s'),
                'hora_salida' => $this->salida_at?->format('H:i:s'),
            ]);
    }

    /**
     * Registros para la vista/JSON. Si la asistencia es histórica y aún no
     * tiene filas en asistencia_hijos, las deriva del JSON legado.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int|null, hijo_id: int|null, nombre: string, estado: string, ficha: string|null, entrada_at: string|null, salida_at: string|null}>
     */
    public function registrosNormalizados(): \Illuminate\Support\Collection
    {
        return $this->registrosParaReporte()
            ->map(fn (array $registro) => [
                'id' => $registro['id'],
                'hijo_id' => $registro['hijo_id'],
                'nombre' => $registro['nombre'],
                'estado' => $registro['estado'],
                'ficha' => $registro['ficha'],
                'entrada_at' => $registro['entrada_at']?->toIso8601String(),
                'salida_at' => $registro['salida_at']?->toIso8601String(),
            ]);
    }

    /**
     * Registros listos para reportes. Devuelve Carbon (no strings) para que
     * las vistas puedan formatear horas sin repetir conversiones.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int|null, hijo_id: int|null, nombre: string, estado: string, ficha: string|null, entrada_at: \Carbon\Carbon|null, salida_at: \Carbon\Carbon|null}>
     */
    public function registrosParaReporte(?int $hijoId = null, ?string $estado = null): \Illuminate\Support\Collection
    {
        if ($this->relationLoaded('registros') && $this->registros->isNotEmpty()) {
            $registros = $this->registros;
        } elseif (! $this->relationLoaded('registros') && $this->registros()->exists()) {
            $registros = $this->registros()->get();
        } elseif ($this->relationLoaded('registros') && $this->registros()->exists()) {
            $registros = $this->registros()->get();
        } else {
            $registros = collect();
        }

        if ($registros->isNotEmpty()) {
            return $registros
                ->when($hijoId, fn ($coleccion) => $coleccion->where('hijo_id', $hijoId))
                ->when($estado, fn ($coleccion) => $coleccion->where('estado', $estado))
                ->map(fn (AsistenciaHijo $registro) => [
                    'id' => $registro->id,
                    'hijo_id' => $registro->hijo_id,
                    'nombre' => $registro->nombre,
                    'estado' => $registro->estado,
                    'ficha' => $registro->ficha,
                    'entrada_at' => $registro->entrada_at,
                    'salida_at' => $registro->salida_at,
                ])
                ->values();
        }

        // Los filtros por hijo no se pueden resolver contra el JSON legado;
        // la migración de datos ya creó las filas correspondientes.
        if ($hijoId) {
            return collect();
        }

        return collect($this->hijos_asistencia ?? [])
            ->reject(fn ($valor, $llave) => str_starts_with((string) $llave, 'url_firma_'))
            ->filter(fn ($valor) => is_array($valor) && isset($valor['status']))
            ->when($estado, fn ($coleccion) => $coleccion->filter(fn ($valor) => $valor['status'] === $estado))
            ->map(fn ($valor, $llave) => [
                'id' => null,
                'hijo_id' => null,
                'nombre' => (string) $llave,
                'estado' => (string) $valor['status'],
                'ficha' => $valor['ficha'] ?? null,
                'entrada_at' => $this->entrada_at ?: ($this->hora_entrada ? \Carbon\Carbon::parse($this->hora_entrada) : null),
                'salida_at' => $this->salida_at ?: ($this->hora_salida ? \Carbon\Carbon::parse($this->hora_salida) : null),
            ])
            ->values();
    }
}
