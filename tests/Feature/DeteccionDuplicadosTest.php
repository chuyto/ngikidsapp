<?php

namespace Tests\Feature;

use App\Models\Padre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DeteccionDuplicadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Evita llamadas reales a WalletWallet (el job corre en sync en tests).
        Http::fake([
            '*' => Http::response(['serialNumber' => 'test-serial', 'shareUrl' => 'https://example.com/p/test'], 200),
        ]);
    }

    private function usuarioConPermisos(): User
    {
        foreach (['padres.crear', 'padres.editar', 'padres.ver', 'hijos.crear', 'hijos.editar'] as $permiso) {
            Permission::findOrCreate($permiso);
        }
        $user = User::factory()->create();
        $user->givePermissionTo(['padres.crear', 'padres.editar', 'padres.ver', 'hijos.crear', 'hijos.editar']);

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

    public function test_avisa_cuando_el_telefono_ya_existe_y_no_crea(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre());
        $this->assertDatabaseCount('padres', 1);

        $respuesta = $this->post(route('padres.store'), $this->payloadPadre([
            'nombre' => 'Juan P.',
            'email' => 'otro@example.com',
        ]));

        $respuesta->assertRedirect();
        $respuesta->assertSessionHas('duplicados');
        $this->assertDatabaseCount('padres', 1);
    }

    public function test_crea_si_se_confirma_que_es_diferente(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre());

        // Mismo telefono/nombre pero otro correo (el correo es unico en BD)
        // y confirmacion explicita => se crea el segundo registro.
        $respuesta = $this->post(route('padres.store'), $this->payloadPadre([
            'email' => 'juan.otro@example.com',
            'confirmar_duplicado' => '1',
        ]));

        $respuesta->assertRedirect(route('padres.index'));
        $this->assertDatabaseCount('padres', 2);
    }

    public function test_avisa_cuando_el_hijo_ya_esta_registrado_con_otro_padre(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre());

        $respuesta = $this->post(route('padres.store'), $this->payloadPadre([
            'nombre' => 'Pedro Gomez',
            'telefono' => '5598765432',
            'email' => 'pedro@example.com',
        ]));

        $respuesta->assertSessionHas('duplicados');
        $this->assertNotEmpty(session('duplicados')['hijos']);
        $this->assertDatabaseCount('padres', 1);
    }

    public function test_actualizar_su_propio_registro_no_marca_duplicado(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre());
        $padre = Padre::first();

        $respuesta = $this->put(route('padres.update', $padre), $this->payloadPadre());

        $respuesta->assertRedirect(route('padres.index'));
        $respuesta->assertSessionMissing('duplicados');
    }

    public function test_hijo_duplicado_en_su_propio_formulario(): void
    {
        $this->actingAs($this->usuarioConPermisos());
        $this->post(route('padres.store'), $this->payloadPadre());
        $padre = Padre::first();

        $respuesta = $this->post(route('hijos.store'), [
            'nombre' => 'Mateo Perez',
            'fecha_nacimiento' => '2018-05-06',
            'padre_id' => $padre->id,
        ]);

        $respuesta->assertSessionHas('duplicados');
        $this->assertDatabaseCount('hijos', 1);
    }
}
