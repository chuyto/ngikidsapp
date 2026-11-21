<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                    <i class="fa-solid fa-chart-column text-xl"></i>
                </div>
                <div>
                    <h2 class="page-title leading-tight">{{ __('Reportes de Asistencia') }}</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                        Operaciones · Control de Asistencia
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Panel de Filtros --}}
        <div class="card overflow-hidden">
            <div class="p-8 border-b border-slate-100">
                <h1 class="font-heading text-2xl text-slate-900">Panel de Reportes</h1>
                <p class="mt-1 text-sm text-slate-500 font-semibold">Filtra por servicio, red, niño, estado o fechas, y exporta el reporte oficial.</p>
            </div>

            <div class="p-8">
                <form method="GET" action="{{ route('reportes.index') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 items-end">
                    <div class="xl:col-span-2">
                        <label for="servicio_id" class="label">Servicio</label>
                        <select name="servicio_id" id="servicio_id" class="input">
                            <option value="">-- Selecciona un servicio --</option>
                            @foreach($servicios as $item)
                                <option value="{{ $item->id }}" @selected((int) request('servicio_id') === $item->id)>
                                    {{ $item->descripcion_servicio }} ({{ \Carbon\Carbon::parse($item->fecha_servicio)->format('d/m/Y') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="red_id" class="label">Red</label>
                        <select name="red_id" id="red_id" class="input">
                            <option value="">-- Todas las redes --</option>
                            @foreach($redes as $red)
                                <option value="{{ $red->id }}" @selected((int) request('red_id') === $red->id)>
                                    {{ $red->descripcion }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="hijo_id" class="label">Niño</label>
                        <select name="hijo_id" id="hijo_id" class="input" @disabled($hijos->isEmpty())>
                            <option value="">-- Todos los niños --</option>
                            @foreach($hijos as $hijo)
                                <option value="{{ $hijo->id }}" @selected((int) request('hijo_id') === $hijo->id)>
                                    {{ $hijo->nombre }}{{ $hijo->padre ? ' · '.$hijo->padre->nombre : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="estado" class="label">Estado</label>
                        <select name="estado" id="estado" class="input">
                            <option value="">-- Todos --</option>
                            <option value="asistio" @selected(request('estado') === 'asistio')>Asistió</option>
                            <option value="no_asistio" @selected(request('estado') === 'no_asistio')>No asistió</option>
                        </select>
                    </div>

                    <div>
                        <label for="desde" class="label">Entrada desde</label>
                        <input type="date" name="desde" id="desde" value="{{ request('desde') }}" class="input">
                    </div>

                    <div>
                        <label for="hasta" class="label">Entrada hasta</label>
                        <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}" class="input">
                    </div>

                    <div>
                        <label for="per_page" class="label">Por página</label>
                        <select name="per_page" id="per_page" class="input">
                            @foreach([15, 25, 50] as $size)
                                <option value="{{ $size }}" @selected((int) request('per_page', 25) === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-wrap gap-3 xl:col-span-4">
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Buscar
                        </button>

                        @if(request()->filled('servicio_id'))
                            <a href="{{ route('reportes.pdf', request()->except(['page', 'per_page', 'formato'])) }}"
                               class="btn-danger">
                                <i class="fa-solid fa-file-pdf"></i>
                                Descargar PDF
                            </a>
                            <a href="{{ route('reportes.csv', request()->except(['page', 'per_page', 'formato'])) }}"
                               class="btn-success">
                                <i class="fa-solid fa-file-csv"></i>
                                Descargar CSV
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabla de Resultados --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60 border-y border-slate-100">
                        <tr>
                            <th class="th">Padre / Responsable</th>
                            <th class="th">Red</th>
                            <th class="th">Niños Registrados</th>
                            <th class="th">Entrada</th>
                            <th class="th">Salida</th>
                            <th class="th text-center">Estatus</th>
                            @can('asistencias.editar')
                                <th class="th text-right">Acciones</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($asistencias as $asistencia)
                            @php
                                $registros = $asistencia->registrosParaReporte(
                                    request()->filled('hijo_id') ? request()->integer('hijo_id') : null,
                                    request()->input('estado')
                                );
                            @endphp
                            <tr class="hover:bg-brand-50/40 transition-all duration-150">
                                <td class="td whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-tr from-brand-500 to-pink-400 flex items-center justify-center text-sm font-bold text-white mr-4">
                                            {{ mb_substr($asistencia->padre->nombre ?? '?', 0, 1) }}
                                        </div>
                                        <div>
                                            <a href="{{ $asistencia->padre ? route('padres.show', $asistencia->padre->id) : '#' }}"
                                               class="text-sm font-extrabold text-slate-800 hover:text-brand-600">
                                                {{ $asistencia->padre->nombre ?? 'N/A' }}
                                            </a>
                                            <div class="text-[10px] font-black text-brand-500 uppercase tracking-widest mt-0.5">{{ $asistencia->uuid_short }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="td text-sm font-semibold text-slate-600">
                                    <span class="badge-neutral"><i class="fa-solid fa-layer-group"></i> {{ $asistencia->padre->red->descripcion ?? 'General' }}</span>
                                </td>
                                <td class="td">
                                    <div class="flex flex-wrap gap-2">
                                        @forelse($registros as $registro)
                                            @if($registro['estado'] === 'asistio')
                                                <span class="badge-primary">
                                                    {{ $registro['nombre'] }} <span class="opacity-60">#{{ $registro['ficha'] ?? 'N/A' }}</span>
                                                </span>
                                            @else
                                                <span class="badge-neutral">
                                                    {{ $registro['nombre'] }} <span class="opacity-60">No asistió</span>
                                                </span>
                                            @endif
                                        @empty
                                            <span class="text-slate-400 text-sm font-semibold">Sin coincidencias</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="td text-sm font-bold text-slate-700">
                                    <i class="fa-solid fa-arrow-right-to-bracket text-emerald-500 mr-1.5"></i>{{ $asistencia->formated_entrada }}
                                </td>
                                <td class="td text-sm font-bold text-slate-700">
                                    @if($asistencia->salida_at)
                                        <i class="fa-solid fa-arrow-right-from-bracket text-brand-500 mr-1.5"></i>{{ $asistencia->formated_salida }}
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="td text-center">
                                    @if($asistencia->salida_at)
                                        <span class="badge-success">
                                            <i class="fa-solid fa-check"></i>
                                            Entregado
                                        </span>
                                    @else
                                        <span class="badge-warning">
                                            <i class="fa-solid fa-baby"></i>
                                            En Salón
                                        </span>
                                    @endif
                                </td>
                                @can('asistencias.editar')
                                    <td class="td text-right">
                                        <a href="{{ route('asistencias.edit', $asistencia) }}"
                                           class="inline-flex items-center gap-1.5 text-xs font-extrabold text-slate-500 hover:text-brand-600 transition-colors">
                                            <i class="fa-solid fa-pen-to-square"></i> Corregir
                                        </a>
                                    </td>
                                @endcan
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()?->can('asistencias.editar') ? 7 : 6 }}" class="px-8 py-20 text-center">
                                    <div class="inline-flex p-6 rounded-full bg-slate-100 mb-4">
                                        <i class="fa-solid fa-chart-pie text-4xl text-slate-400"></i>
                                    </div>
                                    <h3 class="font-heading text-xl text-slate-700">Sin registros</h3>
                                    <p class="text-slate-400 mt-1">Selecciona un servicio y ajusta los filtros para ver la asistencia.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($asistencias->isNotEmpty())
                <div class="px-8 py-6 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-2 bg-slate-50/60">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                        Total de asistencias: <span class="text-slate-900">{{ method_exists($asistencias, 'total') ? $asistencias->total() : $asistencias->count() }}</span>
                    </p>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">
                        Total de niños: <span class="text-brand-600">{{ $totalNinos }}</span>
                    </p>
                </div>
            @endif
            @if($asistencias instanceof \Illuminate\Contracts\Pagination\Paginator && $asistencias->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $asistencias->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
