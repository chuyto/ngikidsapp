<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="page-title leading-tight">{{ __('Panel de control') }}</h2>
            <p class="text-sm text-slate-500 mt-1">Resumen general del ministerio</p>
        </div>
    </x-slot>

    <div class="space-y-8">

        @php
            // Quien no puede registrar asistencia va al reporte; así el rol
            // de solo lectura no termina en un 403 desde el dashboard.
            $urlAsistencia = auth()->user()->can('asistencias.crear')
                ? route('asistencias.create')
                : (auth()->user()->can('reportes.ver') ? route('reportes.index') : null);
        @endphp

        @if($servicioActivo)
            <div class="card p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-emerald-200">
                <div class="flex items-center gap-4">
                    <span class="relative flex h-3 w-3"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span></span>
                    <div><p class="text-xs font-black uppercase tracking-widest text-emerald-600">Servicio activo</p><p class="font-heading text-lg text-slate-900">{{ $servicioActivo->descripcion_servicio }}</p></div>
                </div>
                @can('asistencias.crear')<a href="{{ route('asistencias.create') }}" class="btn-success">Registrar asistencia</a>@endcan
            </div>
        @else
            <div class="card p-5 border-amber-200 bg-amber-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div><p class="font-extrabold text-amber-800">No hay un servicio activo</p><p class="text-sm font-semibold text-amber-700">Activa uno antes de registrar entradas.</p></div>
                @can('servicios.editar')<a href="{{ route('servicios.index') }}" class="btn-primary">Ir a servicios</a>@endcan
            </div>
        @endif

        {{-- Tarjetas de métricas --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @can('padres.ver')
            <a href="{{ route('padres.index') }}" class="card card-hover p-6 flex items-center gap-5 cursor-pointer">
                <div class="w-14 h-14 rounded-2xl bg-brand-100 text-brand-600 flex items-center justify-center text-2xl shrink-0">
                    <i class="fa-solid fa-address-book"></i>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-slate-900">{{ $totalPadres }}</p>
                    <p class="text-sm font-semibold text-slate-500">Familias registradas</p>
                </div>
            </a>
            @endcan

            @can('asistencias.ver')
            @if($urlAsistencia)
            <a href="{{ $urlAsistencia }}" class="card card-hover p-6 flex items-center gap-5 cursor-pointer">
                <div class="w-14 h-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-2xl shrink-0"><i class="fa-solid fa-person-circle-exclamation"></i></div>
                <div><p class="text-3xl font-extrabold text-slate-900">{{ $salidasPendientes }}</p><p class="text-sm font-semibold text-slate-500">Salidas pendientes</p></div>
            </a>
            @endif
            @endcan

            @can('hijos.ver')
            <a href="{{ route('padres.index') }}" class="card card-hover p-6 flex items-center gap-5 cursor-pointer">
                <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl shrink-0">
                    <i class="fa-solid fa-children"></i>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-slate-900">{{ $totalHijos }}</p>
                    <p class="text-sm font-semibold text-slate-500">Niños vinculados</p>
                </div>
            </a>
            @endcan

            @can('asistencias.ver')
            @if($urlAsistencia)
            <a href="{{ $urlAsistencia }}" class="card card-hover p-6 flex items-center gap-5 cursor-pointer">
                <div class="w-14 h-14 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl shrink-0">
                    <i class="fa-solid fa-clipboard-check"></i>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-slate-900">{{ $asistenciasHoy }}</p>
                    <p class="text-sm font-semibold text-slate-500">Asistencias de hoy</p>
                </div>
            </a>
            @endif
            @endcan
        </div>

        {{-- Accesos rápidos --}}
        <div class="card p-8">
            <h3 class="section-title mb-6">Accesos rápidos</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @can('asistencias.crear')
                <a href="{{ route('asistencias.create') }}" class="flex items-center gap-4 rounded-2xl bg-brand-50 border border-brand-100 px-5 py-4 transition-all duration-200 hover:bg-brand-100 cursor-pointer">
                    <i class="fa-solid fa-clipboard-check text-brand-600 text-xl w-8 text-center"></i>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">Asistencia</p>
                        <p class="text-xs text-slate-500 font-semibold">Registrar entradas</p>
                    </div>
                </a>
                @endcan
                @can('padres.crear')
                <a href="{{ route('padres.create') }}" class="flex items-center gap-4 rounded-2xl bg-emerald-50 border border-emerald-100 px-5 py-4 transition-all duration-200 hover:bg-emerald-100 cursor-pointer">
                    <i class="fa-solid fa-user-plus text-emerald-600 text-xl w-8 text-center"></i>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">Nueva familia</p>
                        <p class="text-xs text-slate-500 font-semibold">Registrar responsable</p>
                    </div>
                </a>
                @endcan
                @can('reportes.ver')
                <a href="{{ route('reportes.index') }}" class="flex items-center gap-4 rounded-2xl bg-amber-50 border border-amber-100 px-5 py-4 transition-all duration-200 hover:bg-amber-100 cursor-pointer">
                    <i class="fa-solid fa-chart-column text-amber-600 text-xl w-8 text-center"></i>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">Reportes</p>
                        <p class="text-xs text-slate-500 font-semibold">Ver y exportar</p>
                    </div>
                </a>
                @endcan
                @can('servicios.ver')
                <a href="{{ route('servicios.index') }}" class="flex items-center gap-4 rounded-2xl bg-sky-50 border border-sky-100 px-5 py-4 transition-all duration-200 hover:bg-sky-100 cursor-pointer">
                    <i class="fa-solid fa-calendar-days text-sky-600 text-xl w-8 text-center"></i>
                    <div>
                        <p class="text-sm font-extrabold text-slate-800">Servicios</p>
                        <p class="text-xs text-slate-500 font-semibold">Eventos y catálogos</p>
                    </div>
                </a>
                @endcan
            </div>
        </div>

        {{-- Actividad reciente --}}
        <div class="card overflow-hidden">
            <div class="px-8 pt-8 pb-4">
                <h3 class="section-title">Actividad reciente</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="border-y border-slate-100 bg-slate-50/60">
                        <tr>
                            <th class="th">Responsable</th>
                            <th class="th">Folio</th>
                            <th class="th">Entrada</th>
                            <th class="th">Salida</th>
                            <th class="th text-right">Estatus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recientes as $asistencia)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="td font-bold text-slate-800">{{ $asistencia->padre->nombre ?? 'N/A' }}</td>
                                <td class="td">
                                    <span class="badge-neutral">{{ $asistencia->uuid_short }}</span>
                                </td>
                                <td class="td">{{ $asistencia->formated_entrada }}</td>
                                <td class="td">{{ $asistencia->formated_salida }}</td>
                                <td class="td text-right">
                                    @if($asistencia->salida_at)
                                        <span class="badge-success"><i class="fa-solid fa-circle-check"></i> Entregado</span>
                                    @else
                                        <span class="badge-warning"><i class="fa-solid fa-hourglass-half"></i> En salón</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="td text-center py-16">
                                    <div class="inline-flex p-5 rounded-full bg-slate-100 mb-4">
                                        <i class="fa-solid fa-clipboard-list text-3xl text-slate-400"></i>
                                    </div>
                                    <p class="font-bold text-slate-600">Aún no hay asistencias registradas</p>
                                    <p class="text-sm text-slate-400 mt-1">Inicia el registro desde el menú de Asistencia.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
