<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Listado de usuarios.
     */
    public function index(Request $request)
    {
        $perPage = in_array((int) $request->input('per_page', 15), [15, 30, 50], true) ? (int) $request->input('per_page', 15) : 15;
        $usuarios = User::with('roles', 'permissions')
            ->when($request->filled('search'), fn ($query) => $query
                ->where(fn ($inner) => $inner->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('email', 'like', '%'.$request->search.'%')
                    ->orWhereHas('roles', fn ($role) => $role->where('name', 'like', '%'.$request->search.'%'))))
            ->orderBy('name')->paginate($perPage)->withQueryString();
        $roles = Role::orderBy('name')->get();
        $permisos = Permission::orderBy('name')->get();

        return view('usuarios.index', compact('usuarios', 'roles', 'permisos'));
    }

    /**
     * Formulario para crear un usuario.
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();
        $permisos = Permission::orderBy('name')->get();

        return view('usuarios.create', compact('roles', 'permisos'));
    }

    /**
     * Guarda un usuario nuevo con su rol y permisos.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        if (! empty($data['roles'])) {
            Gate::authorize('usuarios.asignar-roles');
        }
        if (! empty($data['permissions'])) {
            Gate::authorize('usuarios.asignar-permisos');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $user->syncRoles($data['roles'] ?? []);
        $user->syncPermissions($data['permissions'] ?? []);

        AuditLogger::log(
            'usuario.creado',
            $user,
            'Alta de usuario '.$user->email,
            [],
            ['roles' => $user->getRoleNames()->values()->all(), 'permisos' => $user->getPermissionNames()->values()->all()],
        );

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Formulario para editar un usuario.
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $permisos = Permission::orderBy('name')->get();

        return view('usuarios.edit', compact('user', 'roles', 'permisos'));
    }

    /**
     * Actualiza el usuario, su rol y permisos.
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')],
        ]);

        if (array_key_exists('roles', $data)) {
            Gate::authorize('usuarios.asignar-roles');
        }
        if (array_key_exists('permissions', $data)) {
            Gate::authorize('usuarios.asignar-permisos');
        }

        // Protección del último administrador: un usuario con permiso de
        // edición no puede dejar el sistema sin ningún Administrador.
        if ($user->id !== $request->user()->id
            && array_key_exists('roles', $data)
            && $user->hasRole('Administrador')
            && $this->esUltimoAdministrador($user)) {
            $nombresNuevos = Role::whereIn('id', $data['roles'] ?? [])->pluck('name');

            if (! $nombresNuevos->contains('Administrador')) {
                return back()->withInput()->with('error', 'No puedes quitar el rol de Administrador al último administrador del sistema.');
            }
        }

        $antes = [
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values()->all(),
            'permisos' => $user->getPermissionNames()->values()->all(),
        ];

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        // Protección: al editar la propia cuenta no se modifican roles ni
        // permisos. Así nadie puede autoasignarse Administrador, y un
        // administrador tampoco puede quitarse su propio acceso.
        if ($user->id !== $request->user()->id) {
            if (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles'] ?? []);
            }
            if (array_key_exists('permissions', $data)) {
                $user->syncPermissions($data['permissions'] ?? []);
            }
        }

        AuditLogger::log(
            'usuario.actualizado',
            $user,
            'Actualización de usuario '.$user->email,
            $antes,
            [
                'name' => $user->name,
                'email' => $user->email,
                'password_cambiada' => ! empty($data['password']),
                'roles' => $user->getRoleNames()->values()->all(),
                'permisos' => $user->getPermissionNames()->values()->all(),
            ],
        );

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Elimina un usuario (imposible borrarse a sí mismo).
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        if ($this->esUltimoAdministrador($user)) {
            return back()->with('error', 'No puedes eliminar al último administrador del sistema.');
        }

        AuditLogger::log(
            'usuario.eliminado',
            $user,
            'Eliminación de usuario '.$user->email,
            ['roles' => $user->getRoleNames()->values()->all()],
        );

        $user->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }

    private function esUltimoAdministrador(User $user): bool
    {
        if (! $user->hasRole('Administrador')) {
            return false;
        }

        return ! User::query()
            ->whereKeyNot($user->id)
            ->whereHas('roles', fn ($query) => $query->where('name', 'Administrador'))
            ->exists();
    }
}
