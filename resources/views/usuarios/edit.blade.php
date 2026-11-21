<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-user-pen text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Editar Usuario') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                    Administración · {{ $user->name }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card overflow-hidden">

            {{-- Header Superior --}}
            <div class="p-8 border-b border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-xl font-extrabold shrink-0">
                        {{ mb_substr($user->name, 0, 1) }}
                    </div>
                    <div>
                        <h3 class="font-heading text-xl text-slate-900">Modificar Datos del Usuario</h3>
                        <p class="text-sm font-semibold text-slate-500">Ajusta rol, permisos y credenciales de acceso.</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('usuarios.update', $user) }}" method="POST" class="p-8 space-y-8">
                @csrf
                @method('PUT')

                @if($user->id === auth()->id())
                    <div class="flex items-start gap-3 rounded-2xl bg-amber-50 border border-amber-200 px-5 py-4 text-sm font-semibold text-amber-800">
                        <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                        <span>Estás editando tu propia cuenta. Los roles y permisos no se pueden modificar desde aquí.</span>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="label">Nombre completo</label>
                        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" class="input" required autofocus>
                        @error('name')
                            <p class="mt-2 text-sm font-bold text-brand-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="label">Correo electrónico</label>
                        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required>
                        @error('email')
                            <p class="mt-2 text-sm font-bold text-brand-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="password" class="label">Nueva contraseña <span class="text-slate-400 font-semibold">(opcional)</span></label>
                        <input id="password" type="password" name="password" class="input" autocomplete="new-password" placeholder="Dejar vacío para no cambiar">
                        @error('password')
                            <p class="mt-2 text-sm font-bold text-brand-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="label">Confirmar contraseña</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" class="input" autocomplete="new-password">
                    </div>
                </div>

                @php
                    // En la propia cuenta solo se editan datos y contraseña.
                    $permitirCambio = $user->id !== auth()->id();
                @endphp

                {{-- Rol --}}
                @can('usuarios.asignar-roles')
                <div>
                    <label class="label">Rol</label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach($roles as $rol)
                            <label class="glass {{ $permitirCambio ? 'cursor-pointer hover:bg-white/90' : 'cursor-not-allowed opacity-60' }} rounded-2xl px-4 py-3 flex items-center gap-3 transition-colors" for="rol-{{ $rol->id }}">
                                <input type="radio" name="roles[]" id="rol-{{ $rol->id }}" value="{{ $rol->id }}"
                                       class="rounded-full border-slate-300 text-brand-600 focus:ring-brand-500"
                                       @checked(old('roles', $user->roles->pluck('id')->toArray()) ? in_array($rol->id, old('roles', $user->roles->pluck('id')->toArray())) : false)
                                       @disabled(!$permitirCambio)>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $rol->name }}</p>
                                    <p class="text-[11px] font-semibold text-slate-400">{{ $rol->permissions->count() }} permisos</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('roles')
                        <p class="mt-2 text-sm font-bold text-brand-600">{{ $message }}</p>
                    @enderror
                </div>
                @endcan

                {{-- Permisos adicionales --}}
                @can('usuarios.asignar-permisos')
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <label class="label mb-0">Permisos adicionales</label>
                        <p class="text-[11px] font-semibold text-slate-400">Se suman a los del rol seleccionado.</p>
                    </div>

                    @php
                        $actualPermisos = old('permissions', $user->permissions->pluck('id')->toArray());
                        $agrupados = $permisos->groupBy(fn($p) => str($p->name)->before('.'));
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($agrupados as $modulo => $modPermisos)
                            <div class="rounded-2xl border border-slate-200/70 bg-white/50 p-4 space-y-2">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 flex items-center gap-2">
                                    <i class="fa-solid fa-cubes"></i> {{ ucfirst($modulo) }}
                                </p>
                                @foreach($modPermisos as $permiso)
                                    <label class="flex items-center gap-2.5 cursor-pointer {{ $permitirCambio ? '' : 'opacity-60' }}">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $permiso->id }}"
                                               class="rounded-md border-slate-300 text-brand-600 focus:ring-brand-500"
                                               @checked(!$permitirCambio || in_array($permiso->id, $actualPermisos))
                                               @disabled(!$permitirCambio)>
                                        <span class="text-sm font-semibold text-slate-600">{{ str($permiso->name)->after('.')->headline() }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                    @error('permissions')
                        <p class="mt-2 text-sm font-bold text-brand-600">{{ $message }}</p>
                    @enderror
                </div>
                @endcan

                {{-- Footer de Acciones --}}
                <div class="flex items-center justify-between pt-8 border-t border-slate-100">
                    <a href="{{ route('usuarios.index') }}" class="text-xs font-black text-slate-400 hover:text-slate-600 transition uppercase tracking-widest">
                        Cancelar
                    </a>
                    @if($user->id !== auth()->id())
                        <button type="submit" class="btn-primary px-8 py-3">
                            <i class="fa-solid fa-rotate"></i>
                            GUARDAR CAMBIOS
                        </button>
                    @else
                        <button type="submit" class="btn-primary px-8 py-3">
                            <i class="fa-solid fa-rotate"></i>
                            ACTUALIZAR PERFIL
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-app-layout>