<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\AsistenciaHijo;
use App\Models\Hijo;
use App\Models\Padre;
use App\Models\Servicio;
use App\Models\User;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AsistenciaPorNinoTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        foreach (['asistencias.crear', 'asistencias.editar', 'asistencias.eliminar'] as $permiso) {
            Permission::findOrCreate($permiso);
        }

        $this->usuario = User::factory()->create();
        $this->usuario->givePermissionTo(['asistencias.crear', 'asistencias.editar', 'asistencias.eliminar']);

        // Cloudinary no debe salir a la red durante las pruebas.
        $uploadApi = \Mockery::mock(\Cloudinary\Api\Upload\UploadApi::class);
        $uploadApi->shouldReceive('upload')->andReturn(new \Cloudinary\Api\ApiResponse([
            'secure_url' => 'https://res.cloudinary.com/demo/image/upload/asistencias/firmas/firma.png',
            'public_id' => 'asistencias/firmas/firma',
        ], []));
        $uploadApi->shouldReceive('destroy')->andReturn(new \Cloudinary\Api\ApiResponse(['result' => 'ok'], []));

        Cloudinary::shouldReceive('uploadApi')->andReturn($uploadApi);
    }

    private function servicioActivo(): Servicio
    {
        return Servicio::create([
            'descripcion_servicio' => 'Dominical',
            'horario_servicio' => '10:00',
            'fecha_servicio' => now()->toDateString(),
            'activo' => true,
        ]);
    }

    /**
     * @param  array<int, array{nombre: string, fecha_nacimiento: string}>  $hijos
     */
    private function familia(array $hijos = []): Padre
    {
        $padre = Padre::create([
            'nombre' => 'Familia de Prueba',
            'parentesco' => 'Madre',
            'telefono' => '5512345678',
            'email' => 'familia'.fake()->unique()->numberBetween(1, 9999).'@example.test',
            'uuid_short' => fake()->unique()->regexify('[A-Z0-9]{6}'),
        ]);

        foreach ($hijos ?: [
            ['nombre' => 'Ana Prueba', 'fecha_nacimiento' => '2018-05-06'],
        ] as $datos) {
            $padre->hijos()->create([
                'nombre' => $datos['nombre'],
                'fecha_nacimiento' => $datos['fecha_nacimiento'],
                'edad' => 7,
            ]);
        }

        return $padre->load('hijos');
    }

    private function firma(): string
    {
        return 'data:image/png;base64,'.base64_encode('firma-de-prueba');
    }

    /**
     * @param  array<int, array{estado: string, ficha?: string|null}>  $registros
     */
    private function entrar(Padre $padre, array $registros): array
    {
        $payload = [];

        foreach ($padre->hijos as $indice => $hijo) {
            $datos = $registros[$indice] ?? ['estado' => 'no_asistio'];
            $payload[$hijo->id] = [
                'status' => $datos['estado'],
                'ficha' => $datos['ficha'] ?? null,
            ];
        }

        return $this->actingAs($this->usuario)
            ->postJson(route('asistencias.store'), [
                'uuid_short' => $padre->uuid_short,
                'hijos' => $payload,
                'signature' => $this->firma(),
            ])
            ->assertOk()
            ->json();
    }

    public function test_entrada_crea_un_registro_por_nino_y_sincroniza_el_json_legado(): void
    {
        $this->servicioActivo();
        $padre = $this->familia([
            ['nombre' => 'Ana Prueba', 'fecha_nacimiento' => '2018-05-06'],
            ['nombre' => 'Luis Prueba', 'fecha_nacimiento' => '2020-01-15'],
        ]);

        $respuesta = $this->entrar($padre, [
            ['estado' => 'asistio', 'ficha' => '101'],
            ['estado' => 'no_asistio'],
        ]);

        $this->assertSame('¡Ingreso registrado con éxito!', $respuesta['success']);

        $asistencia = Asistencia::firstOrFail();
        $this->assertNotNull($asistencia->entrada_at);
        $this->assertNull($asistencia->salida_at);
        $this->assertCount(2, $asistencia->registros);

        $ana = $asistencia->registros->firstWhere('nombre', 'Ana Prueba');
        $luis = $asistencia->registros->firstWhere('nombre', 'Luis Prueba');

        $this->assertSame('asistio', $ana->estado);
        $this->assertSame('101', $ana->ficha);
        $this->assertNotNull($ana->entrada_at);
        $this->assertSame('no_asistio', $luis->estado);
        $this->assertNull($luis->entrada_at);

        // El JSON legado se conserva para compatibilidad/rollback.
        $json = $asistencia->fresh()->hijos_asistencia;
        $this->assertSame('asistio', $json['Ana Prueba']['status']);
        $this->assertSame('no_asistio', $json['Luis Prueba']['status']);
        $this->assertArrayHasKey('url_firma_entrada', $json);

        // Bitácora de la entrada.
        $this->assertDatabaseHas('asistencia_cambios', [
            'asistencia_id' => $asistencia->id,
            'accion' => 'entrada',
        ]);
    }

    public function test_salida_cierra_solo_los_registros_asistentes(): void
    {
        $this->servicioActivo();
        $padre = $this->familia([
            ['nombre' => 'Ana Prueba', 'fecha_nacimiento' => '2018-05-06'],
            ['nombre' => 'Luis Prueba', 'fecha_nacimiento' => '2020-01-15'],
        ]);

        $this->entrar($padre, [
            ['estado' => 'asistio', 'ficha' => '101'],
            ['estado' => 'no_asistio'],
        ]);

        $respuesta = $this->actingAs($this->usuario)
            ->postJson(route('asistencias.store'), [
                'uuid_short' => $padre->uuid_short,
                'signature' => $this->firma(),
            ])
            ->assertOk()
            ->json();

        $this->assertSame('¡Salida y entrega registrada correctamente!', $respuesta['success']);

        $asistencia = Asistencia::firstOrFail();
        $this->assertNotNull($asistencia->salida_at);

        $ana = $asistencia->registros()->where('nombre', 'Ana Prueba')->firstOrFail();
        $luis = $asistencia->registros()->where('nombre', 'Luis Prueba')->firstOrFail();

        $this->assertNotNull($ana->salida_at);
        $this->assertNull($luis->salida_at);
        $this->assertDatabaseHas('asistencia_cambios', [
            'asistencia_id' => $asistencia->id,
            'accion' => 'salida',
        ]);
        $this->assertArrayHasKey('url_firma_salida', $asistencia->fresh()->hijos_asistencia);
    }

    public function test_correccion_actualiza_registros_y_deja_bitacora_con_motivo(): void
    {
        $this->servicioActivo();
        $padre = $this->familia();

        $this->entrar($padre, [['estado' => 'asistio', 'ficha' => '101']]);
        $asistencia = Asistencia::with('registros')->firstOrFail();
        $registro = $asistencia->registros->firstOrFail();

        $this->actingAs($this->usuario)
            ->put(route('asistencias.update', $asistencia), [
                'motivo' => 'Se capturó el nombre equivocado',
                'registros' => [
                    $registro->id => [
                        'estado' => 'no_asistio',
                        'ficha' => '101',
                    ],
                ],
            ])
            ->assertRedirect(route('asistencias.edit', $asistencia));

        $registro->refresh();
        $this->assertSame('no_asistio', $registro->estado);
        $this->assertNull($registro->ficha);
        $this->assertNull($registro->entrada_at);
        $this->assertSame('Se capturó el nombre equivocado', $registro->motivo_correccion);

        $this->assertDatabaseHas('asistencia_cambios', [
            'asistencia_id' => $asistencia->id,
            'asistencia_hijo_id' => $registro->id,
            'accion' => 'correccion',
            'motivo' => 'Se capturó el nombre equivocado',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'asistencia.corregida',
        ]);
    }

    public function test_anulacion_conserva_el_registro_y_lo_audita(): void
    {
        $this->servicioActivo();
        $padre = $this->familia();

        $this->entrar($padre, [['estado' => 'asistio', 'ficha' => '101']]);
        $asistencia = Asistencia::firstOrFail();

        $this->actingAs($this->usuario)
            ->delete(route('asistencias.destroy', $asistencia), [
                'motivo' => 'Registro duplicado',
            ])
            ->assertRedirect(route('padres.show', $padre));

        $this->assertSoftDeleted('asistencias', ['id' => $asistencia->id]);
        $this->assertSame(1, AsistenciaHijo::where('asistencia_id', $asistencia->id)->count());

        $this->assertDatabaseHas('asistencia_cambios', [
            'asistencia_id' => $asistencia->id,
            'accion' => 'anulacion',
            'motivo' => 'Registro duplicado',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'asistencia.anulada',
        ]);
    }

    public function test_payload_legado_por_nombre_de_nino_sigue_funcionando(): void
    {
        $this->servicioActivo();
        $padre = $this->familia([
            ['nombre' => 'Ana Prueba', 'fecha_nacimiento' => '2018-05-06'],
            ['nombre' => 'Luis Prueba', 'fecha_nacimiento' => '2020-01-15'],
        ]);

        $this->actingAs($this->usuario)
            ->postJson(route('asistencias.store'), [
                'uuid_short' => $padre->uuid_short,
                'hijos_asistencia' => [
                    'Ana Prueba' => ['status' => 'asistio', 'ficha' => '202'],
                    'Luis Prueba' => ['status' => 'no_asistio'],
                ],
                'signature' => $this->firma(),
            ])
            ->assertOk();

        $asistencia = Asistencia::with('registros')->firstOrFail();
        $ana = $asistencia->registros->firstWhere('nombre', 'Ana Prueba');
        $this->assertSame('asistio', $ana->estado);
        $this->assertSame('202', $ana->ficha);
        $this->assertSame($padre->hijos->firstWhere('nombre', 'Ana Prueba')->id, $ana->hijo_id);
    }

    public function test_no_permite_reutilizar_una_ficha_en_el_mismo_servicio(): void
    {
        $this->servicioActivo();

        $primera = $this->familia([['nombre' => 'Ana Prueba', 'fecha_nacimiento' => '2018-05-06']]);
        $segunda = $this->familia([['nombre' => 'Luis Prueba', 'fecha_nacimiento' => '2020-01-15']]);

        $this->entrar($primera, [['estado' => 'asistio', 'ficha' => '101']]);

        $this->actingAs($this->usuario)
            ->postJson(route('asistencias.store'), [
                'uuid_short' => $segunda->uuid_short,
                'hijos' => [
                    $segunda->hijos->first()->id => ['status' => 'asistio', 'ficha' => '101'],
                ],
                'signature' => $this->firma(),
            ])
            ->assertStatus(422)
            ->assertJson(['error' => 'La ficha #101 ya fue asignada a otro niño.']);

        $this->assertSame(0, Asistencia::where('uuid_short', $segunda->uuid_short)->count());
    }

    public function test_no_permite_asistencia_sin_ningun_nino_marcado(): void
    {
        $this->servicioActivo();
        $padre = $this->familia();

        $this->actingAs($this->usuario)
            ->postJson(route('asistencias.store'), [
                'uuid_short' => $padre->uuid_short,
                'hijos' => [
                    $padre->hijos->first()->id => ['status' => 'no_asistio'],
                ],
                'signature' => $this->firma(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hijos');

        $this->assertDatabaseCount('asistencias', 0);
    }

    public function test_el_buscador_devuelve_los_registros_por_nino(): void
    {
        $this->servicioActivo();
        $padre = $this->familia();

        $this->entrar($padre, [['estado' => 'asistio', 'ficha' => '101']]);
        $asistencia = Asistencia::with('registros')->firstOrFail();
        $registro = $asistencia->registros->firstOrFail();

        $respuesta = $this->actingAs($this->usuario)
            ->getJson(route('asistencias.search', ['uuid_short' => $padre->uuid_short]))
            ->assertOk()
            ->json('padre');

        $this->assertSame($registro->id, $respuesta['registros'][$registro->hijo_id]['id']);
        $this->assertSame('101', $respuesta['registros'][$registro->hijo_id]['ficha']);
        $this->assertSame('asistio', $respuesta['hijos_asistencia']['Ana Prueba']['status']);
    }

    public function test_una_asistencia_historica_sin_registros_se_deriva_del_json(): void
    {
        $this->servicioActivo();
        $padre = $this->familia();

        $asistencia = Asistencia::create([
            'uuid_short' => $padre->uuid_short,
            'servicio_id' => Servicio::first()->id,
            'hora_entrada' => '10:00:00',
            'entrada_at' => now()->setTime(10, 0),
            'hijos_asistencia' => [
                'Ana Prueba' => ['status' => 'asistio', 'ficha' => '303'],
            ],
        ]);

        $this->actingAs($this->usuario)
            ->get(route('asistencias.edit', $asistencia))
            ->assertOk();

        // Al abrir la corrección se generan los registros individuales.
        $this->assertDatabaseHas('asistencia_hijos', [
            'asistencia_id' => $asistencia->id,
            'nombre' => 'Ana Prueba',
            'hijo_id' => $padre->hijos->first()->id,
            'estado' => 'asistio',
            'ficha' => '303',
        ]);
    }

    public function test_la_anulacion_rechaza_motivo_vacio(): void
    {
        $this->servicioActivo();
        $padre = $this->familia();
        $this->entrar($padre, [['estado' => 'asistio', 'ficha' => '101']]);
        $asistencia = Asistencia::firstOrFail();

        $this->actingAs($this->usuario)
            ->deleteJson(route('asistencias.destroy', $asistencia), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');

        $this->assertNotSoftDeleted('asistencias', ['id' => $asistencia->id]);
    }

    public function test_el_listado_de_hijos_deriva_la_edad_de_la_fecha(): void
    {
        $this->familia([['nombre' => 'Ana Prueba', 'fecha_nacimiento' => '2018-05-06']]);
        $hijo = Hijo::firstOrFail();

        $this->assertSame(\Carbon\Carbon::parse('2018-05-06')->age, $hijo->edad);
    }
}
