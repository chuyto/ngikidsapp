<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                    <i class="fa-solid fa-address-book text-xl"></i>
                </div>
                <div>
                    <h2 class="page-title leading-tight">{{ __('Lista de Padres e Hijos') }}</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                        Administración · Directorio General
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Panel Principal --}}
        <div class="card overflow-hidden">

            {{-- Toolbar Superior con Buscador --}}
            <div class="p-8 border-b border-slate-100 flex flex-col lg:flex-row justify-between items-center gap-6">
                <div>
                    <h1 class="font-heading text-2xl text-slate-900">Padres e Hijos</h1>
                    <p class="mt-1 text-sm text-slate-500 font-semibold">Listado general de registros y credenciales.</p>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-4 w-full lg:w-auto">
                    @can('padres.crear')
                    <x-create-modal title="Registrar Padre e Hijos" icon="fa-user-plus">
                        @include('padres._form')
                    </x-create-modal>
                    @endcan
                </div>
            </div>

            <x-table-toolbar :action="route('padres.index')" placeholder="Buscar nombre, teléfono o credencial..."
                             :sorts="['recientes' => 'Más recientes', 'antiguos' => 'Más antiguos', 'nombre' => 'Nombre A–Z']" />

            {{-- Pestañas Activos / Papelera --}}
            <div class="px-8 pt-4 flex items-center gap-2">
                <a href="{{ route('padres.index', array_filter(['search' => request('search'), 'sort' => request('sort')])) }}"
                   class="px-4 py-2 rounded-full text-xs font-extrabold uppercase tracking-widest transition {{ empty($papelera) ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                    <i class="fa-solid fa-users mr-1"></i> Activos
                </a>
                <a href="{{ route('padres.index', array_filter(['search' => request('search'), 'sort' => request('sort'), 'papelera' => 1])) }}"
                   class="px-4 py-2 rounded-full text-xs font-extrabold uppercase tracking-widest transition {{ ! empty($papelera) ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                    <i class="fa-solid fa-trash-can mr-1"></i> Papelera
                </a>
            </div>

            {{-- Tabla Estilizada --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60 border-y border-slate-100">
                        <tr>
                            <th class="th">Nombre del Padre</th>
                            <th class="th">Credencial</th>
                            <th class="th">Teléfono</th>
                            <th class="th">Red</th>
                            <th class="th">Niños</th>
                            <th class="relative"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($padres as $padre)
                            <tr class="hover:bg-brand-50/40 transition-all duration-150">
                                <td class="td whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-sm font-extrabold shrink-0">
                                            {{ mb_substr($padre->nombre, 0, 1) }}
                                        </div>
                                        <span class="font-extrabold text-slate-800">{{ $padre->nombre }}</span>
                                    </div>
                                </td>

                                {{-- COLUMNA CREDENCIAL CON CLICK PARA COPIAR --}}
                                <td class="td whitespace-nowrap">
                                    <div x-data="{
                                            copied: false,
                                            copyToClipboard(text) {
                                                navigator.clipboard.writeText(text);
                                                this.copied = true;
                                                setTimeout(() => this.copied = false, 2000);
                                            }
                                         }" class="relative flex items-center">
                                        <button @click="copyToClipboard('{{ $padre->uuid_short }}')"
                                                class="group relative inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 text-white font-mono font-bold text-xs shadow-sm hover:bg-slate-700 transition-all active:scale-95 cursor-pointer">
                                            <i class="fa-solid fa-copy text-[10px] opacity-50 group-hover:opacity-100"></i>
                                            {{ $padre->uuid_short }}

                                            <div x-show="copied"
                                                 x-transition:enter="transition ease-out duration-200"
                                                 x-transition:enter-start="opacity-0 -translate-y-2"
                                                 x-transition:enter-end="opacity-100 translate-y-0"
                                                 class="absolute -top-9 left-1/2 -translate-x-1/2 px-3 py-1 bg-emerald-600 text-white text-[10px] font-bold uppercase rounded-lg shadow-xl z-50 pointer-events-none">
                                                <i class="fa-solid fa-check mr-1"></i>¡Copiado!
                                            </div>
                                        </button>
                                    </div>
                                </td>

                                <td class="td whitespace-nowrap">
                                    <span class="inline-flex items-center gap-2 font-semibold text-slate-600">
                                        <i class="fa-solid fa-phone text-slate-400 text-xs"></i>{{ $padre->telefono }}
                                    </span>
                                </td>
                                <td class="td whitespace-nowrap">
                                    @if($padre->red)
                                        <span class="badge-info"><i class="fa-solid fa-circle-nodes"></i> {{ $padre->red->descripcion }}</span>
                                    @else
                                        <span class="badge-neutral">Sin red</span>
                                    @endif
                                </td>
                                <td class="td">
                                    <div class="flex flex-wrap gap-2 max-w-[300px]">
                                        @forelse ($padre->hijos as $hijo)
                                            <span class="badge-neutral">
                                                <i class="fa-solid fa-baby text-brand-400"></i> {{ $hijo->nombre }}
                                            </span>
                                        @empty
                                            <span class="text-xs font-semibold text-slate-400">Sin hijos registrados</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="td whitespace-nowrap text-right">
                                    @if (! empty($papelera))
                                        <div class="inline-flex items-center gap-2">
                                            @can('padres.editar')
                                            <form action="{{ route('padres.restore', $padre->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="btn-ghost px-4 py-2 text-xs" title="Restaurar registro">
                                                    <i class="fa-solid fa-trash-arrow-up"></i> RESTAURAR
                                                </button>
                                            </form>
                                            @endcan
                                            @can('padres.eliminar')
                                            <form action="{{ route('padres.forceDelete', $padre->id) }}" method="POST" class="inline" data-confirm="¿Eliminar DEFINITIVAMENTE a {{ $padre->nombre }}? No se puede deshacer.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-ghost px-4 py-2 text-xs !text-brand-600" title="Eliminar definitivamente">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-2">
                                            <a href="{{ route('padres.show', $padre->id) }}" class="btn-ghost px-4 py-2 text-xs">
                                                <i class="fa-solid fa-eye"></i> VER MÁS
                                            </a>
                                            @can('padres.eliminar')
                                            <form action="{{ route('padres.destroy', $padre->id) }}" method="POST" class="inline" data-confirm="¿Enviar a la papelera a {{ $padre->nombre }}? Podrás restaurarlo después.">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-ghost px-4 py-2 text-xs !text-brand-600" title="Enviar a papelera" aria-label="Enviar a papelera">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                            @endcan
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="td p-16 text-center">
                                    <div class="inline-flex p-6 rounded-full bg-slate-100 mb-4">
                                        <i class="fa-solid fa-users-slash text-4xl text-slate-400"></i>
                                    </div>
                                    <h3 class="text-xl font-bold text-slate-700">No se encontraron registros</h3>
                                    <p class="text-slate-400 mt-1">Intenta con otro término de búsqueda o crea uno nuevo.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Paginación Estilizada --}}
            <div class="px-8 py-6 border-t border-slate-100 bg-slate-50/40">
                {{ $padres->appends(['search' => request('search'), 'sort' => request('sort'), 'papelera' => request('papelera')])->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
