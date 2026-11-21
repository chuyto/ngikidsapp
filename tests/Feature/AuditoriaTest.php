<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Servicio;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private function administrador(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Administrador');

        return $user;
    }

    private function operador(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Operador');

        return $user;
    }

    private function registrar(
        string $action,
        ?string $description = null,
        ?User $user = null,
        ?string $createdAt = null,
    ): AuditLog {
        $log = AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'old_values' => ['estado' => 'anterior'],
            'new_values' => ['estado' => 'nuevo'],
            'ip' => '192.0.2.10',
        ]);

        if ($createdAt) {
            $log->forceFill(['created_at' => $createdAt])->save();
        }

        return $log;
    }

    public function test_un_administrador_puede_ver_la_bitacora(): void
    {
        $admin = $this->administrador();
        $this->registrar('padre.creado', 'Alta de la familia de prueba', $admin);

        $respuesta = $this->actingAs($admin)->get(route('auditoria.index'));

        $respuesta->assertOk()
            ->assertSee('Bitácora del sistema')
            ->assertSee('Familia creada')
            ->assertSee('Alta de la familia de prueba')
            ->assertSee('192.0.2.10')
            ->assertSee('Ver datos');
    }

    public function test_un_operador_no_puede_ver_ni_exportar_la_bitacora(): void
    {
        $operador = $this->operador();

        $this->actingAs($operador)->get(route('auditoria.index'))->assertForbidden();
        $this->actingAs($operador)->get(route('auditoria.exportar'))->assertForbidden();
    }

    public function test_los_filtros_limitan_los_resultados(): void
    {
        $admin = $this->administrador();
        $otro = User::factory()->create();

        $this->registrar('padre.creado', 'Movimiento de familia', $admin, now()->subDays(5)->toDateTimeString());
        $this->registrar('hijo.actualizado', 'Movimiento de niño', $otro);
        $this->registrar('asistencia.anulada', 'Movimiento de asistencia', $otro);

        $this->actingAs($admin)
            ->get(route('auditoria.index', ['modulo' => 'hijo']))
            ->assertOk()
            ->assertSee('Movimiento de niño')
            ->assertDontSee('Movimiento de familia')
            ->assertDontSee('Movimiento de asistencia');

        $this->actingAs($admin)
            ->get(route('auditoria.index', ['accion' => 'asistencia.anulada']))
            ->assertOk()
            ->assertSee('Movimiento de asistencia')
            ->assertDontSee('Movimiento de niño');

        $this->actingAs($admin)
            ->get(route('auditoria.index', ['user_id' => $admin->id]))
            ->assertOk()
            ->assertSee('Movimiento de familia')
            ->assertDontSee('Movimiento de niño');

        $this->actingAs($admin)
            ->get(route('auditoria.index', ['buscar' => 'familia']))
            ->assertOk()
            ->assertSee('Movimiento de familia')
            ->assertDontSee('Movimiento de asistencia');

        $this->actingAs($admin)
            ->get(route('auditoria.index', ['desde' => now()->subDay()->toDateString()]))
            ->assertOk()
            ->assertDontSee('Movimiento de familia')
            ->assertSee('Movimiento de niño');
    }

    public function test_la_exportacion_csv_incluye_los_movimientos_y_queda_auditada(): void
    {
        $admin = $this->administrador();
        $this->registrar('padre.creado', 'Familia exportable', $admin);

        $respuesta = $this->actingAs($admin)->get(route('auditoria.exportar'));

        $respuesta->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $contenido = $respuesta->streamedContent();
        $this->assertStringContainsString('Familia creada', $contenido);
        $this->assertStringContainsString('Familia exportable', $contenido);
        $this->assertStringContainsString('192.0.2.10', $contenido);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'auditoria.exportada',
            'user_id' => $admin->id,
        ]);
    }

    public function test_las_sesiones_y_las_descargas_de_reportes_quedan_auditadas(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Administrador');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sesion.iniciada',
            'user_id' => $user->id,
        ]);

        $servicio = Servicio::create([
            'descripcion_servicio' => 'Dominical',
            'horario_servicio' => '10:00',
            'fecha_servicio' => now()->toDateString(),
            'activo' => true,
        ]);

        $this->get(route('reportes.csv', ['servicio_id' => $servicio->id]))
            ->assertOk()
            ->streamedContent();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'reporte.csv',
            'user_id' => $user->id,
        ]);

        $this->post('/logout')->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sesion.cerrada',
            'user_id' => $user->id,
        ]);
    }

    public function test_un_intento_fallido_de_sesion_queda_auditado(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'contraseña-incorrecta',
        ]);

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sesion.fallida',
            'user_id' => $user->id,
        ]);
    }
}
