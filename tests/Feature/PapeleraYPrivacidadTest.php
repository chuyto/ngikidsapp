<?php

namespace Tests\Feature;

use App\Models\Hijo;
use App\Models\Padre;
use App\Models\Red;
use App\Models\Servicio;
use App\Models\User;
use App\Services\ReportePdfCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PapeleraYPrivacidadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            '*' => Http::response(['serialNumber' => 'test-serial', 'shareUrl' => 'https://example.com/p/test'], 200),
        ]);
    }

    private function usuarioConPermisos(): User
    {
        foreach (['padres.crear', 'padres.ver', 'padres.editar', 'padres.eliminar', 'hijos.crear', 'hijos.ver', 'hijos.editar', 'hijos.eliminar', 'reportes.ver'] as $permiso) {
            Permission::findOrCreate($permiso);
        }
        $user = User::factory()->create();
        $user->givePermissionTo(['padres.crear', 'padres.ver', 'padres.editar', 'padres.eliminar', 'hijos.crear', 'hijos.ver', 'hijos.editar', 'hijos.eliminar', 'reportes.ver']);

        return $user;
    }

    private function payloadPadre(array $sobrescribir = []): array
    {
        return array_merge([
            'nombre' => 'Juan Perez',
            'parentesco' => 'Padre',
            'telefono' => '5512345678',
            'email' => 'juan@example.com',
            'privacidad' => '1',
            'hijos' => [
                ['nombre' => 'Mateo Perez', 'fecha_nacimiento' => '2018-05-06'],
            ],
        ], $sobrescribir);
    }

    public function test_eliminar_envia_a_papelera_con_sus_hijos_y_se_puede_restaurar(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre());
        $padre = Padre::first();

        $this->delete(route('padres.destroy', $padre));

        $this->assertSoftDeleted('padres', ['id' => $padre->id]);
        $this->assertSoftDeleted('hijos', ['padre_id' => $padre->id]);
        $this->assertEquals(0, Padre::count());

        $this->post(route('padres.restore', $padre->id));

        $this->assertNotSoftDeleted('padres', ['id' => $padre->id]);
        $this->assertNotSoftDeleted('hijos', ['padre_id' => $padre->id]);
    }

    public function test_eliminar_definitivamente_borra_el_registro(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre());
        $padre = Padre::first();

        $this->delete(route('padres.destroy', $padre));
        $this->delete(route('padres.forceDelete', $padre->id));

        $this->assertDatabaseMissing('padres', ['id' => $padre->id]);
    }

    public function test_el_duplicado_avisa_si_esta_en_papelera(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre());
        $padre = Padre::first();
        $this->delete(route('padres.destroy', $padre));

        $respuesta = $this->post(route('padres.store'), $this->payloadPadre(['email' => 'nuevo@example.com']));

        $respuesta->assertSessionHas('duplicados');
        $padres = session('duplicados')['padres'];
        $this->assertNotEmpty($padres);
        $this->assertTrue($padres[0]['eliminado']);
    }

    public function test_hijo_eliminado_se_restaura(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre());
        $hijo = Hijo::first();

        $this->delete(route('hijos.destroy', $hijo));
        $this->assertSoftDeleted('hijos', ['id' => $hijo->id]);

        $this->post(route('hijos.restore', $hijo->id));
        $this->assertNotSoftDeleted('hijos', ['id' => $hijo->id]);
    }

    public function test_registro_exige_aceptar_el_aviso_de_privacidad(): void
    {
        $this->actingAs($this->usuarioConPermisos());

        $respuesta = $this->post(route('padres.store'), $this->payloadPadre(['privacidad' => null]));
        $respuesta->assertSessionHasErrors('privacidad');
        $this->assertDatabaseCount('padres', 0);

        $this->post(route('padres.store'), $this->payloadPadre());
        $this->assertNotNull(Padre::first()->privacidad_aceptada_at);
    }

    public function test_reporte_pdf_se_cachea(): void
    {
        Storage::fake('local');
        $this->actingAs($this->usuarioConPermisos());
        $servicio = Servicio::create([
            'descripcion_servicio' => 'Dominical',
            'horario_servicio' => '10:00',
            'fecha_servicio' => now()->toDateString(),
            'activo' => true,
        ]);

        $respuesta = $this->get(route('reportes.pdf', ['servicio_id' => $servicio->id]));

        $respuesta->assertOk();
        $respuesta->assertHeader('Content-Type', 'application/pdf');

        // Servicio sin asistencias: la firma es determinista (0_0_0) y la
        // llave incluye todos los filtros activos.
        $llave = 'reporte_pdf_'.$servicio->id.'_'.md5(json_encode([
            'red_id' => null,
            'hijo_id' => null,
            'estado' => null,
            'desde' => null,
            'hasta' => null,
            'firma' => '0_0_0',
        ]));
        $this->assertNotNull(app(ReportePdfCache::class)->get($llave));

        // Segunda peticion: mismo binario servido desde cache.
        $segunda = $this->get(route('reportes.pdf', ['servicio_id' => $servicio->id]));
        $segunda->assertOk();
        $this->assertEquals($respuesta->getContent(), $segunda->getContent());
    }

    public function test_el_buscador_no_muestra_registros_en_papelera(): void
    {
        $this->actingAs($this->usuarioConPermisos());

        $this->post(route('padres.store'), $this->payloadPadre([
            'nombre' => 'Buscado Activo',
            'email' => 'activo@example.com',
        ]));

        $this->post(route('padres.store'), $this->payloadPadre([
            'nombre' => 'Buscado Eliminado',
            'telefono' => '5599999999',
            'email' => 'eliminado@example.com',
            'hijos' => [
                ['nombre' => 'Hijo Eliminado', 'fecha_nacimiento' => '2018-05-06'],
            ],
        ]));
        $eliminado = Padre::where('email', 'eliminado@example.com')->firstOrFail();

        $this->delete(route('padres.destroy', $eliminado));

        $respuesta = $this->get(route('padres.index', ['search' => 'Buscado']));

        $respuesta->assertOk();
        $respuesta->assertSee('Buscado Activo');
        $respuesta->assertDontSee('Buscado Eliminado');
    }

    public function test_editar_padre_conserva_ids_y_elimina_solo_los_hijos_quitados(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre([
            'hijos' => [
                ['nombre' => 'Mateo Perez', 'fecha_nacimiento' => '2018-05-06'],
                ['nombre' => 'Lucia Perez', 'fecha_nacimiento' => '2020-08-10'],
            ],
        ]));
        $padre = Padre::firstOrFail();
        $mateo = $padre->hijos()->where('nombre', 'Mateo Perez')->firstOrFail();
        $lucia = $padre->hijos()->where('nombre', 'Lucia Perez')->firstOrFail();

        $respuesta = $this->put(route('padres.update', $padre), $this->payloadPadre([
            'confirmar_duplicado' => 1,
            'hijos' => [
                ['id' => $mateo->id, 'nombre' => 'Mateo Perez Actualizado', 'fecha_nacimiento' => '2018-05-06'],
                ['nombre' => 'Nuevo Hijo', 'fecha_nacimiento' => '2022-01-15'],
            ],
        ]));

        $respuesta->assertRedirect(route('padres.index'));

        // El hijo conservado mantiene su ID (y por tanto su historial) y se
        // actualiza en sitio; el quitado se envía a papelera y el nuevo nace.
        $this->assertDatabaseHas('hijos', [
            'id' => $mateo->id,
            'nombre' => 'Mateo Perez Actualizado',
            'deleted_at' => null,
        ]);
        $this->assertSoftDeleted('hijos', ['id' => $lucia->id]);
        $this->assertDatabaseHas('hijos', [
            'padre_id' => $padre->id,
            'nombre' => 'Nuevo Hijo',
            'deleted_at' => null,
        ]);
        $this->assertSame(2, $padre->hijos()->count());
    }

    public function test_restaurar_padre_no_restaura_un_hijo_eliminado_individualmente(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre([
            'hijos' => [
                ['nombre' => 'Mateo Perez', 'fecha_nacimiento' => '2018-05-06'],
                ['nombre' => 'Lucia Perez', 'fecha_nacimiento' => '2020-08-10'],
            ],
        ]));
        $padre = Padre::firstOrFail();
        $conservado = $padre->hijos()->where('nombre', 'Mateo Perez')->firstOrFail();
        $eliminadoAntes = $padre->hijos()->where('nombre', 'Lucia Perez')->firstOrFail();

        $this->delete(route('hijos.destroy', $eliminadoAntes));
        $this->delete(route('padres.destroy', $padre));
        $this->post(route('padres.restore', $padre->id));

        $this->assertNotSoftDeleted('hijos', ['id' => $conservado->id]);
        $this->assertSoftDeleted('hijos', ['id' => $eliminadoAntes->id]);
    }

    public function test_reporte_pdf_genera_una_cache_distinta_por_red(): void
    {
        Storage::fake('local');
        $this->actingAs($this->usuarioConPermisos());
        $servicio = Servicio::create([
            'descripcion_servicio' => 'Dominical',
            'horario_servicio' => '10:00',
            'fecha_servicio' => now()->toDateString(),
            'activo' => true,
        ]);
        $redA = Red::create(['descripcion' => 'Red A']);
        $redB = Red::create(['descripcion' => 'Red B']);

        $this->get(route('reportes.pdf', [
            'servicio_id' => $servicio->id,
            'red_id' => $redA->id,
        ]))->assertOk();

        $this->get(route('reportes.pdf', [
            'servicio_id' => $servicio->id,
            'red_id' => $redB->id,
        ]))->assertOk();

        $llaveA = 'reporte_pdf_'.$servicio->id.'_'.md5(json_encode([
            'red_id' => $redA->id,
            'hijo_id' => null,
            'estado' => null,
            'desde' => null,
            'hasta' => null,
            'firma' => '0_0_0',
        ]));
        $llaveB = 'reporte_pdf_'.$servicio->id.'_'.md5(json_encode([
            'red_id' => $redB->id,
            'hijo_id' => null,
            'estado' => null,
            'desde' => null,
            'hasta' => null,
            'firma' => '0_0_0',
        ]));

        $this->assertNotSame($llaveA, $llaveB);
        $cache = app(ReportePdfCache::class);
        $this->assertNotNull($cache->get($llaveA));
        $this->assertNotNull($cache->get($llaveB));
    }
}
