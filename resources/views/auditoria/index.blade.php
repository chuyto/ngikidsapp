<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                    <i class="fa-solid fa-clipboard-list text-xl"></i>
                </div>
                <div>
                    <h2 class="page-title leading-tight">{{ __('Auditoría') }}</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                        Administración · Bitácora de movimientos
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        {{-- Panel de filtros --}}
        <div class="card overflow-hidden">
            <div class="p-8 border-b border-slate-100">
                <h1 class="font-heading text-2xl text-slate-900">Bitácora del sistema</h1>
                <p class="mt-1 text-sm text-slate-500 font-semibold">
                    Consulta quién hizo cada cambio, cuándo, desde qué IP y qué valores se modificaron.
                </p>
            </div>

            <div class="p-8">
                @if($errors->any())
                    <div class="mb-5 rounded-2xl border border-brand-200 bg-brand-50/70 px-5 py-4">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-triangle-exclamation text-brand-600 mt-0.5"></i>
                            <ul class="list-disc list-inside space-y-1 text-sm font-semibold text-brand-700">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <form method="GET" action="{{ route('auditoria.index') }}"
                      class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 items-end">
                    <div class="xl:col-span-2">
                        <label for="buscar" class="label">Buscar</label>
                        <input type="search" name="buscar" id="buscar" value="{{ request('buscar') }}"
                               class="input" placeholder="Descripción, usuario, correo, acción o IP..." autocomplete="off">
                    </div>

                    <div>
                        <label for="modulo" class="label">Módulo</label>
                        <select name="modulo" id="modulo" class="input">
                            <option value="">-- Todos los módulos --</option>
                            @foreach($modulos as $clave => $etiqueta)
                                <option value="{{ $clave }}" @selected(request('modulo') === $clave)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="accion" class="label">Acción</label>
                        <select name="accion" id="accion" class="input">
                            <option value="">-- Todas las acciones --</option>
                            @foreach($acciones as $accion)
                                <option value="{{ $accion }}" @selected(request('accion') === $accion)>
                                    {{ \App\Models\AuditLog::etiquetaDe($accion) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="user_id" class="label">Usuario</label>
                        <select name="user_id" id="user_id" class="input">
                            <option value="">-- Todos los usuarios --</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" @selected((string) request('user_id') === (string) $usuario->id)>
                                    {{ $usuario->name }} · {{ $usuario->email }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="desde" class="label">Desde</label>
                        <input type="date" name="desde" id="desde" value="{{ request('desde') }}" class="input">
                    </div>

                    <div>
                        <label for="hasta" class="label">Hasta</label>
                        <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}" class="input">
                    </div>

                    <div>
                        <label for="per_page" class="label">Por página</label>
                        <select name="per_page" id="per_page" class="input">
                            @foreach([25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((int) request('per_page', 50) === $size)>{{ $size }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-wrap gap-3 xl:col-span-4">
                        <button type="submit" class="btn-primary">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            Filtrar
                        </button>

                        <a href="{{ route('auditoria.index') }}" class="btn-secondary">
                            <i class="fa-solid fa-rotate-left"></i>
                            Limpiar
                        </a>

                        <a href="{{ route('auditoria.exportar', request()->query()) }}" class="btn-secondary">
                            <i class="fa-solid fa-file-csv"></i>
                            Exportar CSV
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Resultados --}}
        <div class="card overflow-hidden">
            <div class="p-8 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="font-heading text-xl text-slate-900">Movimientos registrados</h3>
                    <p class="mt-1 text-sm font-semibold text-slate-500">
                        La bitácora es de solo lectura y no se puede editar desde el sistema.
                    </p>
                </div>
                <span class="badge-neutral">
                    <i class="fa-solid fa-list-check"></i>
                    {{ $logs->total() }} {{ $logs->total() === 1 ? 'registro' : 'registros' }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/60 border-y border-slate-100">
                        <tr>
                            <th class="th">Fecha</th>
                            <th class="th">Usuario</th>
                            <th class="th">Acción</th>
                            <th class="th">Entidad</th>
                            <th class="th">Descripción</th>
                            <th class="th">IP</th>
                            <th class="th">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($logs as $log)
                            @php
                                $antes = $log->old_values
                                    ? json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                    : null;
                                $despues = $log->new_values
                                    ? json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                    : null;
                            @endphp
                            <tr class="hover:bg-brand-50/40 transition-all duration-150 align-top">
                                <td class="td whitespace-nowrap">
                                    <span class="font-bold text-slate-700">{{ $log->created_at?->format('d/m/Y') }}</span>
                                    <span class="block text-xs font-semibold text-slate-400">{{ $log->created_at?->format('H:i:s') }}</span>
                                </td>

                                <td class="td whitespace-nowrap">
                                    @if($log->user)
                                        <span class="block font-extrabold text-slate-800">{{ $log->user->name }}</span>
                                        <span class="block text-xs font-semibold text-slate-400">{{ $log->user->email }}</span>
                                    @else
                                        <span class="block font-bold text-slate-500">Sistema</span>
                                        <span class="block text-xs font-semibold text-slate-400">Usuario no disponible</span>
                                    @endif
                                </td>

                                <td class="td whitespace-nowrap">
                                    <span class="{{ $log->tono() }}">{{ $log->etiqueta() }}</span>
                                    <span class="mt-1 block font-mono text-[10px] font-semibold text-slate-400">{{ $log->action }}</span>
                                </td>

                                <td class="td whitespace-nowrap">
                                    @if($log->entidad())
                                        <span class="font-bold text-slate-700">{{ $log->entidad() }}</span>
                                        @if($log->auditable_id)
                                            <span class="block text-xs font-semibold text-slate-400">#{{ $log->auditable_id }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>

                                <td class="td max-w-xs">
                                    <p class="font-semibold text-slate-700 break-words">
                                        {{ $log->description ?: 'Sin descripción' }}
                                    </p>
                                </td>

                                <td class="td whitespace-nowrap">
                                    <span class="font-mono text-xs font-semibold text-slate-500">{{ $log->ip ?: '—' }}</span>
                                </td>

                                <td class="td">
                                    @if($antes || $despues)
                                        <details class="group min-w-[220px]">
                                            <summary class="cursor-pointer list-none text-xs font-extrabold text-brand-600 hover:text-brand-700">
                                                <i class="fa-solid fa-chevron-right mr-1 transition-transform group-open:rotate-90"></i>
                                                Ver datos
                                            </summary>
                                            <div class="mt-3 space-y-3">
                                                @if($antes)
                                                    <div>
                                                        <p class="mb-1 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Antes</p>
                                                        <pre class="max-h-56 overflow-auto rounded-xl border border-slate-200 bg-slate-50 p-3 text-[11px] font-medium text-slate-600 whitespace-pre-wrap break-all">{{ $antes }}</pre>
                                                    </div>
                                                @endif
                                                @if($despues)
                                                    <div>
                                                        <p class="mb-1 text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Después</p>
                                                        <pre class="max-h-56 overflow-auto rounded-xl border border-slate-200 bg-slate-50 p-3 text-[11px] font-medium text-slate-600 whitespace-pre-wrap break-all">{{ $despues }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="inline-flex p-6 rounded-full bg-slate-100 mb-4">
                                        <i class="fa-solid fa-clipboard-question text-4xl text-slate-400"></i>
                                    </div>
                                    <h3 class="font-heading text-xl text-slate-700">Sin movimientos</h3>
                                    <p class="text-slate-400 mt-1">No hay registros que coincidan con los filtros seleccionados.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $logs->links() }}</div>
            @endif

            <div class="px-8 py-5 border-t border-slate-100 bg-slate-50/50">
                <p class="text-xs font-semibold text-slate-500">
                    <i class="fa-solid fa-lock mr-1 text-slate-400"></i>
                    Registro de solo lectura. Los valores anteriores y nuevos pueden contener datos personales;
                    el acceso está limitado a administradores.
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
