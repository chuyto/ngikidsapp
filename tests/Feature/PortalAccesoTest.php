<?php

namespace Tests\Feature;

use App\Models\Padre;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PortalAccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_ve_portal_de_acceso_con_login_integrado(): void
    {
        $respuesta = $this->get('/');

        $respuesta->assertOk();
        $respuesta->assertSee('Acceso del equipo');
        $respuesta->assertSee('Iniciar sesi', false);
        $respuesta->assertSee('Aviso de Privacidad', false);
        $respuesta->assertDontSee('Registrar asistencia');
    }

    public function test_equipo_ve_saludo_accesos_y_servicio_activo(): void
    {
        foreach (['asistencias.crear', 'padres.ver', 'reportes.ver', 'servicios.ver'] as $permiso) {
            Permission::findOrCreate($permiso);
        }
        $user = User::factory()->create(['name' => 'Ana Operadora']);
        $user->givePermissionTo(['asistencias.crear', 'padres.ver', 'reportes.ver', 'servicios.ver']);
        $this->actingAs($user);

        Servicio::create([
            'descripcion_servicio' => 'Dominical',
            'horario_servicio' => '10:00',
            'fecha_servicio' => now()->toDateString(),
            'activo' => true,
        ]);

        $respuesta = $this->get('/');

        $respuesta->assertOk();
        $respuesta->assertSee('Hola, Ana');
        $respuesta->assertSee('Registrar asistencia');
        $respuesta->assertSee('Directorio de familias');
        $respuesta->assertSee('Dominical');
    }

    public function test_aviso_de_privacidad_es_publico(): void
    {
        $respuesta = $this->get(route('privacidad'));

        $respuesta->assertOk();
        $respuesta->assertSee('Aviso de Privacidad', false);
    }

    public function test_rol_lectura_no_ve_enlaces_de_registro_de_asistencia(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Lectura');
        $this->actingAs($user);

        $padre = Padre::create([
            'nombre' => 'Familia Lectura',
            'parentesco' => 'Madre',
            'telefono' => '5512345678',
            'email' => 'lectura@example.test',
        ]);

        // El rol Lectura puede consultar, pero no debe recibir enlaces que
        // terminen en 403 al registrar asistencia.
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('/asistencias/create');

        $this->get(route('padres.show', $padre))
            ->assertOk()
            ->assertDontSee('/asistencias/create');

        $servicio = Servicio::create([
            'descripcion_servicio' => 'Dominical',
            'horario_servicio' => '10:00',
            'fecha_servicio' => now()->toDateString(),
            'activo' => true,
        ]);

        // Los reportes sí están permitidos para este rol.
        $this->get(route('servicios.index'))
            ->assertOk()
            ->assertSee(route('reportes.index', ['servicio_id' => $servicio->id]));
    }
}
