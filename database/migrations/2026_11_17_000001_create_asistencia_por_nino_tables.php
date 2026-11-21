<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            // entrada_at/salida_at son la fuente de verdad. Las columnas TIME
            // legadas se conservan por compatibilidad, pero dejan de ser
            // obligatorias para permitir registros nuevos sin duplicar datos.
            $table->time('hora_entrada')->nullable()->change();
            $table->time('hora_salida')->nullable()->change();

            $table->dateTime('entrada_at')->nullable();
            $table->dateTime('salida_at')->nullable();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('salida_registrada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('firma_entrada_public_id')->nullable();
            $table->string('firma_salida_public_id')->nullable();
            $table->foreignId('anulada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_anulacion')->nullable();
            $table->softDeletes();
            $table->index(['servicio_id', 'entrada_at'], 'asistencias_servicio_entrada_index');
        });

        Schema::create('asistencia_hijos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asistencia_id')->constrained('asistencias')->cascadeOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();
            $table->foreignId('hijo_id')->nullable()->constrained('hijos')->nullOnDelete();
            $table->string('nombre');
            $table->string('estado', 20)->default('no_asistio');
            $table->string('ficha', 50)->nullable();
            $table->dateTime('entrada_at')->nullable();
            $table->dateTime('salida_at')->nullable();
            $table->foreignId('registrado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('salida_registrada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('corregido_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_correccion')->nullable();
            $table->timestamps();

            $table->unique(['asistencia_id', 'hijo_id'], 'asistencia_hijos_asistencia_hijo_unique');
            $table->unique(['servicio_id', 'ficha'], 'asistencia_hijos_servicio_ficha_unique');
            $table->index(['hijo_id', 'servicio_id'], 'asistencia_hijos_hijo_servicio_index');
        });

        Schema::create('asistencia_cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asistencia_id')->constrained('asistencias')->cascadeOnDelete();
            $table->foreignId('asistencia_hijo_id')->nullable()->constrained('asistencia_hijos')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion', 30);
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();
        });

        $this->backfill();
    }

    /**
     * Migra el JSON histórico (keyed por nombre de niño) a registros por niño.
     */
    private function backfill(): void
    {
        DB::table('asistencias')->orderBy('id')->chunkById(200, function ($asistencias) {
            foreach ($asistencias as $asistencia) {
                $servicio = $asistencia->servicio_id
                    ? DB::table('servicios')->where('id', $asistencia->servicio_id)->first(['fecha_servicio'])
                    : null;

                $fechaBase = $servicio?->fecha_servicio
                    ? Carbon::parse($servicio->fecha_servicio)->toDateString()
                    : Carbon::parse($asistencia->created_at ?? now())->toDateString();

                $entradaAt = $asistencia->hora_entrada
                    ? Carbon::parse($fechaBase.' '.$asistencia->hora_entrada)
                    : ($asistencia->created_at ? Carbon::parse($asistencia->created_at) : now());

                $salidaAt = null;
                if ($asistencia->hora_salida) {
                    $salidaAt = Carbon::parse($fechaBase.' '.$asistencia->hora_salida);
                    if ($salidaAt->lt($entradaAt)) {
                        $salidaAt->addDay();
                    }
                }

                DB::table('asistencias')->where('id', $asistencia->id)->update([
                    'entrada_at' => $entradaAt,
                    'salida_at' => $salidaAt,
                ]);

                $json = $asistencia->hijos_asistencia;
                if (is_string($json)) {
                    $json = json_decode($json, true);
                }

                if (! is_array($json)) {
                    continue;
                }

                $padre = DB::table('padres')->where('uuid_short', $asistencia->uuid_short)->first(['id']);

                foreach ($json as $nombre => $datos) {
                    if (! is_array($datos) || ! isset($datos['status']) || str_starts_with((string) $nombre, 'url_firma_')) {
                        continue;
                    }

                    if (! in_array($datos['status'], ['asistio', 'no_asistio'], true)) {
                        continue;
                    }

                    $nombre = trim((string) $nombre);
                    if ($nombre === '') {
                        continue;
                    }

                    $hijo = null;
                    if ($padre) {
                        $hijo = DB::table('hijos')
                            ->where('padre_id', $padre->id)
                            ->where('nombre', $nombre)
                            ->whereNull('deleted_at')
                            ->orderBy('id')
                            ->first(['id']);

                        if (! $hijo) {
                            $hijo = DB::table('hijos')
                                ->where('padre_id', $padre->id)
                                ->where('nombre', $nombre)
                                ->orderBy('id')
                                ->first(['id']);
                        }
                    }

                    $ficha = trim((string) ($datos['ficha'] ?? ''));
                    $asistio = $datos['status'] === 'asistio';

                    DB::table('asistencia_hijos')->insertOrIgnore([
                        'asistencia_id' => $asistencia->id,
                        'servicio_id' => $asistencia->servicio_id,
                        'hijo_id' => $hijo?->id,
                        'nombre' => $nombre,
                        'estado' => $datos['status'],
                        'ficha' => $ficha !== '' ? $ficha : null,
                        'entrada_at' => $asistio ? $entradaAt : null,
                        'salida_at' => $asistio ? $salidaAt : null,
                        'registrado_por_id' => null,
                        'salida_registrada_por_id' => null,
                        'created_at' => $asistencia->created_at ?? now(),
                        'updated_at' => $asistencia->updated_at ?? now(),
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencia_cambios');
        Schema::dropIfExists('asistencia_hijos');

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropForeign(['registrado_por_id']);
            $table->dropForeign(['salida_registrada_por_id']);
            $table->dropForeign(['anulada_por_id']);
            $table->dropIndex('asistencias_servicio_entrada_index');
            $table->dropSoftDeletes();
            $table->dropColumn([
                'entrada_at',
                'salida_at',
                'firma_entrada_public_id',
                'firma_salida_public_id',
                'registrado_por_id',
                'salida_registrada_por_id',
                'anulada_por_id',
                'motivo_anulacion',
            ]);
        });
    }
};
