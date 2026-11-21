<form action="{{ route('usuarios.store') }}" method="POST" class="space-y-8" data-modal-form>
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="name" class="label">Nombre completo</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" class="input" required autofocus>
        </div>
        <div>
            <label for="email" class="label">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" required>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="password" class="label">Contraseña</label>
            <input id="password" type="password" name="password" class="input" required autocomplete="new-password">
        </div>
        <div>
            <label for="password_confirmation" class="label">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
        </div>
    </div>

    {{-- Rol --}}
    @can('usuarios.asignar-roles')
    <div>
        <label class="label">Rol</label>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach($roles as $rol)
                <label class="glass cursor-pointer rounded-2xl px-4 py-3 flex items-center gap-3 hover:bg-white/90 transition-colors" for="rol-{{ $rol->id }}">
                    <input type="radio" name="roles[]" id="rol-{{ $rol->id }}" value="{{ $rol->id }}"
                           class="rounded-full border-slate-300 text-brand-600 focus:ring-brand-500">
                    <div>
                        <p class="text-sm font-bold text-slate-800">{{ $rol->name }}</p>
                        <p class="text-[11px] font-semibold text-slate-400">{{ $rol->permissions->count() }} permisos</p>
                    </div>
                </label>
            @endforeach
        </div>
    </div>
    @endcan

    {{-- Permisos adicionales --}}
    @can('usuarios.asignar-permisos')
    <div>
        <div class="flex items-center justify-between mb-3">
            <label class="label mb-0">Permisos adicionales</label>
            <button type="button"
                    x-data="{ on: false }"
                    @click="on = !on; $el.parentElement.parentElement.querySelectorAll('[data-perm]').forEach(e => e.checked = on)"
                    class="text-xs font-black text-brand-600 hover:text-brand-800 uppercase tracking-widest cursor-pointer">
                Marcar / desmarcar todos
            </button>
        </div>

        @php
            $agrupados = $permisos->groupBy(fn($p) => str($p->name)->before('.'));
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($agrupados as $modulo => $modPermisos)
                <div class="rounded-2xl border border-slate-200/70 bg-white/50 p-4 space-y-2">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 flex items-center gap-2">
                        <i class="fa-solid fa-cubes"></i> {{ ucfirst($modulo) }}
                    </p>
                    @foreach($modPermisos as $permiso)
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $permiso->id }}"
                                   data-perm
                                   class="rounded-md border-slate-300 text-brand-600 focus:ring-brand-500">
                            <span class="text-sm font-semibold text-slate-600">{{ str($permiso->name)->after('.')->headline() }}</span>
                        </label>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
    @endcan

    <div class="pt-6 border-t border-slate-100">
        <button type="submit" class="btn-primary w-full py-3">
            <i class="fa-solid fa-check"></i>
            CREAR USUARIO
        </button>
    </div>
</form>