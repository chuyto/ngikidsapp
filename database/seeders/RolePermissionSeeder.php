<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos por módulo y acción.
        $modulos = [
            'padres' => 'Registro de padres',
            'hijos' => 'Registro de hijos',
            'asistencias' => 'Asistencia',
            'reportes' => 'Reportes',
            'servicios' => 'Servicios',
            'redes' => 'Redes',
            'supervisores' => 'Supervisores',
            'usuarios' => 'Usuarios',
        ];

        $acciones = ['ver', 'crear', 'editar', 'eliminar'];

        $permisos = [];
        foreach ($modulos as $modulo => $etiqueta) {
            foreach ($acciones as $accion) {
                $permisos[] = "{$modulo}.{$accion}";
            }
        }
        $permisos[] = 'usuarios.asignar-roles';
        $permisos[] = 'usuarios.asignar-permisos';
        // La bitácora es de solo lectura y exclusiva del Administrador.
        $permisos[] = 'auditoria.ver';

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }

        // Rol Administrador: acceso total.
        $admin = Role::firstOrCreate(['name' => 'Administrador']);
        $admin->syncPermissions($permisos);

        // Rol Operador: gestión diaria, sin eliminar y sin gestión de usuarios.
        $operador = Role::firstOrCreate(['name' => 'Operador']);
        $operador->syncPermissions([
            'padres.ver', 'padres.crear', 'padres.editar',
            'hijos.ver', 'hijos.crear', 'hijos.editar',
            'asistencias.ver', 'asistencias.crear', 'asistencias.editar',
            'reportes.ver',
            'servicios.ver',
            'redes.ver',
            'supervisores.ver',
        ]);

        // Rol Lectura: solo visualización y reportes.
        $lectura = Role::firstOrCreate(['name' => 'Lectura']);
        $lectura->syncPermissions([
            'padres.ver',
            'hijos.ver',
            'asistencias.ver',
            'reportes.ver',
            'servicios.ver',
            'redes.ver',
            'supervisores.ver',
        ]);

        // Asigna el rol Administrador al usuario que corresponda del .env.
        $email = env('ADMIN_EMAIL');
        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->assignRole('Administrador');
            }
        }

        // El primer usuario, si aún no tiene rol, se vuelve administrador.
        $firstUser = User::orderBy('id')->first();
        if ($firstUser && ! $firstUser->roles()->exists()) {
            $firstUser->assignRole('Administrador');
        }
    }
}
