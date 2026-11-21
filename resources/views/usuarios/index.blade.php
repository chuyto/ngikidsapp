<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                    <i class="fa-solid fa-user-gear text-xl"></i>
                </div>
                <div>
                    <h2 class="page-title leading-tight">{{ __('Usuarios') }}</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                        Administración · Accesos y Permisos
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Panel Principal --}}
        <div class="card overflow-hidden">

            {{-- Toolbar Superior --}}
            <div class="p-8 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-6">
                <div>
                    <h1 class="font-heading text-2xl text-slate-900">Gestión de Usuarios</h1>
                    <p class="mt-1 text-sm text-slate-500 font-semibold">Crea cuentas y controla qué puede ver y hacer cada quién.</p>
                </div>

                @can('usuarios.crear')
                <x-create-modal title="Nuevo Usuario" icon="fa-user-plus">
                    @include('usuarios._form')
                </x-create-modal>
                @endcan
            </div>

            <x-table-toolbar :action="route('usuarios.index')" placeholder="Buscar nombre, correo o rol..." />

            {{-- Tabla Estilizada --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60 border-y border-slate-100">
                        <tr>
                            <th class="th">Usuario</th>
                            <th class="th">Correo</th>
                            <th class="th">Rol</th>
                            <th class="th">Permisos adicionales</th>
                            <th class="th text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($usuarios as $usuario)
                            <tr class="hover:bg-brand-50/40 transition-all duration-150">
                                <td class="td whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-sm font-extrabold shrink-0">
                                            {{ mb_substr($usuario->name, 0, 1) }}
                                        </div>
                                        <span class="font-extrabold text-slate-800">
                                            {{ $usuario->name }}
                                            @if($usuario->id === auth()->id())
                                                <span class="ml-1 text-[10px] font-bold text-slate-400">(tú)</span>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="td text-sm font-semibold text-slate-600">{{ $usuario->email }}</td>
                                <td class="td whitespace-nowrap">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse($usuario->getRoleNames() as $rol)
                                            <span class="badge-primary"><i class="fa-solid fa-shield-halved"></i> {{ $rol }}</span>
                                        @empty
                                            <span class="badge-neutral">Sin rol</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="td">
                                    @if($usuario->permissions->isNotEmpty())
                                        <span class="badge-info"><i class="fa-solid fa-key"></i> {{ $usuario->permissions->count() }} extra</span>
                                    @else
                                        <span class="text-sm text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="td whitespace-nowrap text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        @can('usuarios.editar')
                                        <a href="{{ route('usuarios.edit', $usuario) }}" class="btn-icon" title="Editar" aria-label="Editar usuario">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        @endcan
                                        @can('usuarios.eliminar')
                                        @if($usuario->id !== auth()->id())
                                            <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST" class="inline"
                                                  data-confirm="¿Eliminar a {{ $usuario->name }}? También perderá su acceso al sistema.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-icon text-brand-500" title="Eliminar" aria-label="Eliminar usuario">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="inline-flex p-6 rounded-full bg-slate-100 mb-4">
                                        <i class="fa-solid fa-user-slash text-4xl text-slate-400"></i>
                                    </div>
                                    <h3 class="font-heading text-xl text-slate-700">Sin usuarios registrados</h3>
                                    <p class="text-slate-400 mt-1">Crea el primer usuario para dar acceso al sistema.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($usuarios->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $usuarios->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
