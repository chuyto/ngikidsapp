<?php

use App\Jobs\GenerateWalletPass;
use App\Mail\PadreRegistered;
use App\Models\Padre;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
});

it('keeps the created parent available for post-commit jobs', function () {
    Mail::fake();
    Bus::fake();

    $user = User::factory()->create();
    Permission::create(['name' => 'padres.crear']);
    $user->givePermissionTo('padres.crear');

    $response = $this->actingAs($user)->post(route('padres.store'), [
        'nombre' => 'María López',
        'parentesco' => 'Madre',
        'telefono' => '5551234567',
        'email' => 'maria@example.test',
        'privacidad' => '1',
        'hijos' => [[
            'nombre' => 'Ana López',
            'fecha_nacimiento' => now()->subYears(7)->toDateString(),
        ]],
    ]);

    $response->assertRedirect(route('padres.index'));
    expect(Padre::where('email', 'maria@example.test')->exists())->toBeTrue();
    Bus::assertDispatched(GenerateWalletPass::class, fn ($job) => $job->queue === 'wallet');
    Mail::assertQueued(PadreRegistered::class);
});

it('forbids assigning roles without the dedicated permission', function () {
    $user = User::factory()->create();
    Permission::create(['name' => 'usuarios.crear']);
    $user->givePermissionTo('usuarios.crear');
    $role = Role::create(['name' => 'Administrador']);

    $response = $this->actingAs($user)->post(route('usuarios.store'), [
        'name' => 'Usuario elevado',
        'email' => 'elevado@example.test',
        'password' => 'PasswordSeguro1',
        'password_confirmation' => 'PasswordSeguro1',
        'roles' => [$role->id],
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'elevado@example.test']);
});

it('does not escalate a user editing their own account', function () {
    $user = User::factory()->create();
    Permission::findOrCreate('usuarios.editar');
    Permission::findOrCreate('usuarios.asignar-roles');
    $user->givePermissionTo(['usuarios.editar', 'usuarios.asignar-roles']);
    $adminRole = Role::create(['name' => 'Administrador']);

    $response = $this->actingAs($user)->put(route('usuarios.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'roles' => [$adminRole->id],
    ]);

    $response->assertRedirect(route('usuarios.index'));
    expect($user->fresh()->hasRole('Administrador'))->toBeFalse();
    expect($user->fresh()->hasPermissionTo('usuarios.asignar-roles'))->toBeTrue();
});

it('renders the user edit form for another user', function () {
    Permission::findOrCreate('usuarios.editar');
    Permission::findOrCreate('usuarios.asignar-roles');
    Permission::findOrCreate('usuarios.asignar-permisos');
    Permission::findOrCreate('padres.ver');
    Role::create(['name' => 'Operador']);
    $admin = User::factory()->create();
    $admin->givePermissionTo(['usuarios.editar', 'usuarios.asignar-roles', 'usuarios.asignar-permisos']);
    $target = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('usuarios.edit', $target));

    $response->assertOk()
        ->assertSee($target->name)
        ->assertSee('name="roles[]"', false)
        ->assertSee('name="permissions[]"', false);
});

it('hides role assignment controls without the dedicated permission', function () {
    Permission::findOrCreate('usuarios.crear');
    $user = User::factory()->create();
    $user->givePermissionTo('usuarios.crear');
    Role::create(['name' => 'Operador']);

    $response = $this->actingAs($user)->get(route('usuarios.create'));

    $response->assertOk()
        ->assertSee('Contraseña')
        ->assertDontSee('name="roles[]"', false)
        ->assertDontSee('name="permissions[]"', false);
});

it('binds and deletes a supervisor using the route parameter', function () {
    $user = User::factory()->create();
    Permission::create(['name' => 'supervisores.eliminar']);
    $user->givePermissionTo('supervisores.eliminar');
    $supervisor = Supervisor::create(['nombre' => 'Supervisor temporal', 'activo' => true]);

    $response = $this->actingAs($user)->delete(route('supervisores.destroy', $supervisor));

    $response->assertRedirect();
    $this->assertDatabaseMissing('supervisors', ['id' => $supervisor->id]);
});

it('paginates supervisors and exposes creation through a modal', function () {
    $user = User::factory()->create();
    Permission::create(['name' => 'supervisores.ver']);
    Permission::create(['name' => 'supervisores.crear']);
    $user->givePermissionTo(['supervisores.ver', 'supervisores.crear']);

    foreach (range(1, 16) as $number) {
        Supervisor::create(['nombre' => "Supervisor {$number}", 'activo' => true]);
    }

    $response = $this->actingAs($user)->get(route('supervisores.index'));

    $response->assertOk()
        ->assertSee('AGREGAR SUPERVISOR')
        ->assertViewHas('supervisores', fn ($items) => $items->count() === 15 && $items->lastPage() === 2);
});

it('filters supervisors and respects the requested page size', function () {
    $user = User::factory()->create();
    Permission::create(['name' => 'supervisores.ver']);
    $user->givePermissionTo('supervisores.ver');

    Supervisor::create(['nombre' => 'Coincidencia Especial', 'activo' => true]);
    Supervisor::create(['nombre' => 'Nombre Diferente', 'activo' => true]);

    $response = $this->actingAs($user)->get(route('supervisores.index', [
        'search' => 'Coincidencia',
        'per_page' => 30,
    ]));

    $response->assertOk()
        ->assertSee('Coincidencia Especial')
        ->assertDontSee('Nombre Diferente')
        ->assertViewHas('supervisores', fn ($items) => $items->perPage() === 30 && $items->total() === 1);
});

it('updates an issued wallet pass with an attendance notification', function () {
    config()->set('services.walletwallet.key', 'test-key');
    Http::fake([
        'https://api.walletwallet.dev/api/passes/*' => Http::response([
            'serialNumber' => 'wallet-serial',
            'shareUrl' => 'https://wallet.test/share',
        ]),
    ]);

    $padre = Padre::create([
        'nombre' => 'Responsable Wallet',
        'parentesco' => 'Madre',
        'telefono' => '5551234567',
        'wallet_serial' => 'wallet-serial',
    ]);

    (new GenerateWalletPass($padre, 'Entrada registrada para Ana · 09/08 10:00.'))->handle();

    Http::assertSent(fn ($request) => $request->method() === 'PUT'
        && $request->url() === 'https://api.walletwallet.dev/api/passes/wallet-serial'
        && collect($request['backFields'])->contains(fn ($field) => ($field['changeMessage'] ?? null) === '%@'
            && str_contains($field['value'], 'Entrada registrada')));
});
