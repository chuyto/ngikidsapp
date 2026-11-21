<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-id-card text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Detalles del Padre') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                    Expediente · Ficha de Registro
                </p>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- Perfil del Padre --}}
        <div class="lg:col-span-1">
            <div class="card overflow-hidden lg:sticky lg:top-24">
                <div class="p-8 flex flex-col items-center">
                    @if($padre->foto_padre)
                        <img class="h-44 w-44 rounded-3xl object-cover border-4 border-white shadow-lg mb-6"
                             src="{{ $padre->foto_padre }}" alt="Foto de {{ $padre->nombre }}">
                    @else
                        <div class="h-44 w-44 rounded-3xl bg-brand-100 text-brand-600 border-4 border-white shadow-lg mb-6 flex items-center justify-center text-6xl font-extrabold" aria-hidden="true">
                            {{ mb_substr($padre->nombre, 0, 1) }}
                        </div>
                    @endif

                    <h3 class="font-heading text-2xl text-slate-900 text-center leading-tight">{{ $padre->nombre }}</h3>

                    <div class="mt-3 mb-4 flex items-center gap-2 flex-wrap justify-center">
                        <span class="badge-info">{{ $padre->parentesco ?? 'Responsable' }}</span>
                        <span class="badge-neutral">{{ $padre->red->descripcion ?? 'Sin Red' }}</span>
                    </div>

                    <div class="w-full mt-6 space-y-4">
                        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-brand-500">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <span class="block text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Teléfono</span>
                                <span class="text-sm font-bold text-slate-700">{{ $padre->telefono }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-sky-500">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div class="min-w-0">
                                <span class="block text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Correo</span>
                                <span class="text-sm font-bold text-slate-800 truncate">{{ $padre->email ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-200">
                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-emerald-500">
                                <i class="fa-solid fa-key"></i>
                            </div>
                            <div>
                                <span class="block text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Credencial</span>
                                <span class="font-mono font-bold text-sm text-slate-800">{{ $padre->uuid_short }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="w-full mt-8 space-y-3">
                        @if ($padre->wallet_share_url)
                            {{-- Tarjeta digital lista --}}
                            <div class="w-full p-4 bg-emerald-50 border border-emerald-200 rounded-2xl">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-white border border-emerald-200 flex items-center justify-center text-emerald-600">
                                            <i class="fa-solid fa-wallet"></i>
                                        </div>
                                        <div>
                                            <span class="block text-[9px] font-extrabold text-emerald-600 uppercase tracking-widest">Wallet</span>
                                            <span class="text-sm font-bold text-slate-700">Tarjeta digital generada</span>
                                        </div>
                                    </div>
                                    <span class="badge-success"><i class="fa-solid fa-check"></i> Lista</span>
                                </div>

                                <div class="mt-4 flex items-center gap-3">
                                    <a href="{{ $padre->wallet_share_url }}"
                                       target="_blank"
                                       class="btn-primary flex-1 !py-2.5 text-xs">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i> Compartir tarjeta
                                    </a>
                                    @php
                                        // Número de WhatsApp del padre en formato internacional (MX: 52 + 10 dígitos)
                                        $waPhone = preg_replace('/\D/', '', $padre->telefono ?? '');
                                        if ($waPhone !== '' && strlen($waPhone) === 10) {
                                            $waPhone = '52'.$waPhone;
                                        }
                                    @endphp
                                    <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode('Tu credencial NGI Kids está lista. Agrégala a tu wallet aquí: ' . $padre->wallet_share_url) }}"
                                       target="_blank"
                                       class="btn-success !py-2.5 text-xs flex-1">
                                        <i class="fa-brands fa-whatsapp"></i> WhatsApp
                                    </a>
                                </div>
                                <p class="mt-3 text-[11px] font-semibold text-emerald-600/80 flex items-center gap-1.5">
                                    <i class="fa-solid fa-eye"></i> Abre el enlace desde el celular del padre para agregarla a su Apple/Google Wallet.
                                </p>
                                @can('padres.editar')
                                    <form action="{{ route('padres.wallet.revocar', $padre) }}" method="POST" class="mt-3"
                                          data-confirm="¿Revocar la tarjeta Wallet? El enlace actual dejará de funcionar.">
                                        @csrf
                                        <button type="submit" class="btn-ghost w-full !text-rose-600 text-xs">
                                            <i class="fa-solid fa-ban"></i> Revocar tarjeta
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        @else
                            {{-- Tarjeta en generación / pendiente --}}
                            <div class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400">
                                        <i class="fa-solid fa-wallet"></i>
                                    </div>
                                    <div>
                                        <span class="block text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Wallet</span>
                                        <span class="text-sm font-bold text-slate-600">
                                            @if ($padre->wallet_serial)
                                                Tarjeta en generación...
                                            @else
                                                Sin tarjeta digital
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <form action="{{ route('padres.wallet', $padre) }}" method="POST" class="mt-4">
                                    @csrf
                                    <button type="submit" class="btn-primary w-full !py-2.5 text-xs">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                        @if ($padre->wallet_serial)
                                            Reintentar generación
                                        @else
                                            Generar tarjeta Wallet
                                        @endif
                                    </button>
                                </form>
                            </div>
                        @endif

                        @can('padres.editar')
                        <a href="{{ route('padres.edit', $padre->id) }}" class="btn-primary w-full">
                            <i class="fa-solid fa-pen"></i> Editar Perfil
                        </a>
                        @endcan
                        @can('asistencias.crear')
                        <a href="{{ route('asistencias.create', ['codigo' => $padre->uuid_short]) }}" class="btn-secondary w-full">
                            <i class="fa-solid fa-clipboard-check"></i> Registrar asistencia
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        {{-- Hijos Vinculados --}}
        <div class="lg:col-span-2 space-y-8">
            @php
                $ultimaAsistencia = $padre->asistencias->first();
                $dentroAhora = $ultimaAsistencia && ! $ultimaAsistencia->salida_at;
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="card p-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Asistencias</p>
                    <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $padre->asistencias->count() }}</p>
                </div>
                <div class="card p-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Estado actual</p>
                    <p class="mt-3 {{ $dentroAhora ? 'badge-warning' : 'badge-success' }}">
                        <i class="fa-solid {{ $dentroAhora ? 'fa-child-reaching' : 'fa-house' }}"></i>
                        {{ $dentroAhora ? 'En el servicio' : 'Sin ingreso pendiente' }}
                    </p>
                </div>
                <div class="card p-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Último movimiento</p>
                    <p class="mt-2 text-sm font-extrabold text-slate-800">
                        {{ ($ultimaAsistencia?->entrada_at ?? $ultimaAsistencia?->created_at)?->format('d/m/Y H:i') ?? 'Sin movimientos' }}
                    </p>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center">
                    <h4 class="font-heading text-xl text-slate-900 flex items-center gap-3">
                        <i class="fa-solid fa-children text-emerald-500"></i> Hijos Vinculados
                    </h4>
                    <span class="badge-neutral">
                        <i class="fa-solid fa-layer-group text-brand-400"></i> {{ $padre->hijos->count() }} registros
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/60 border-y border-slate-100">
                            <tr>
                                <th class="th">Nombre</th>
                                <th class="th">Edad</th>
                                <th class="th text-right">#</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($padre->hijos as $hijo)
                                <tr class="hover:bg-brand-50/40 transition-all">
                                    <td class="td whitespace-nowrap font-extrabold text-slate-800">
                                        <i class="fa-solid fa-baby text-brand-400 mr-3"></i>{{ $hijo->nombre }}
                                    </td>
                                    <td class="td">{{ $hijo->edad !== null ? $hijo->edad.' años' : '—' }}</td>
                                    <td class="td text-right"><span class="text-slate-400 font-mono">#{{ $loop->iteration }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="td py-12 text-center text-slate-400 font-semibold uppercase tracking-[0.2em] text-xs">
                                        <i class="fa-solid fa-user-xmark mr-2"></i>No hay hijos vinculados a este perfil.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center gap-4">
                    <div>
                        <h4 class="font-heading text-xl text-slate-900 flex items-center gap-3">
                            <i class="fa-solid fa-clock-rotate-left text-sky-500"></i> Historial de asistencia
                        </h4>
                        <p class="mt-1 text-sm font-semibold text-slate-500">Entradas, entregas y servicio relacionado.</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($padre->asistencias->take(10) as $asistencia)
                        @php
                            $registros = $asistencia->registrosParaReporte();
                            $ninos = $registros->where('estado', 'asistio');
                        @endphp
                        <div class="px-8 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/60 transition-colors">
                            <div>
                                <p class="font-extrabold text-slate-800">{{ $asistencia->servicio?->descripcion_servicio ?? 'Servicio' }}</p>
                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    {{ $ninos->pluck('nombre')->implode(', ') ?: 'Sin niños marcados' }}
                                </p>
                                @if($ninos->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        @foreach($ninos as $registro)
                                            <span class="badge-primary">{{ $registro['nombre'] }} #{{ $registro['ficha'] ?? 'N/A' }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs font-bold">
                                <span class="badge-success"><i class="fa-solid fa-arrow-right-to-bracket"></i> {{ $asistencia->formated_entrada }}</span>
                                @if($asistencia->salida_at)
                                    <span class="badge-neutral"><i class="fa-solid fa-arrow-right-from-bracket"></i> {{ $asistencia->formated_salida }}</span>
                                @else
                                    <span class="badge-warning">Entrega pendiente</span>
                                @endif
                                @can('asistencias.editar')
                                    <a href="{{ route('asistencias.edit', $asistencia) }}"
                                       class="inline-flex items-center gap-1 text-slate-400 hover:text-brand-600 transition-colors">
                                        <i class="fa-solid fa-pen-to-square"></i> Corregir
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @empty
                        <div class="px-8 py-12 text-center text-slate-400">
                            <i class="fa-solid fa-calendar-xmark text-3xl mb-3"></i>
                            <p class="font-semibold">Todavía no hay asistencias registradas.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="card overflow-hidden">
                <div class="px-8 py-6 border-b border-slate-100">
                    <h4 class="font-heading text-xl text-slate-900 flex items-center gap-3">
                        <i class="fa-solid fa-shield-halved text-brand-500"></i> Privacidad y datos personales
                    </h4>
                    <p class="mt-1 text-sm font-semibold text-slate-500">Evidencia de consentimiento y derechos ARCO.</p>
                </div>
                <div class="p-8 grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200">
                        <p class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Consentimiento</p>
                        <p class="mt-1 text-sm font-bold text-slate-700">
                            {{ $padre->privacidad_aceptada_at?->format('d/m/Y H:i') ?? 'Sin registro' }}
                        </p>
                        <p class="text-xs font-semibold text-slate-500">
                            Versión {{ $padre->privacidad_version ?? '—' }}
                        </p>
                        @if($padre->privacidad_revocada_at)
                            <span class="badge-warning mt-2"><i class="fa-solid fa-ban"></i> Revocado</span>
                        @else
                            <span class="badge-success mt-2"><i class="fa-solid fa-check"></i> Vigente</span>
                        @endif
                    </div>
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200">
                        <p class="text-[9px] font-extrabold text-slate-400 uppercase tracking-widest">Registro técnico</p>
                        <p class="mt-1 text-xs font-semibold text-slate-600 break-all">IP: {{ $padre->privacidad_ip ?? '—' }}</p>
                        <p class="text-xs font-semibold text-slate-500 break-all mt-1">{{ \Illuminate\Support\Str::limit($padre->privacidad_user_agent ?? '—', 120) }}</p>
                    </div>
                </div>
                <div class="px-8 pb-8 flex flex-wrap gap-3">
                    @can('padres.ver')
                        <a href="{{ route('padres.exportar', $padre) }}" class="btn-secondary">
                            <i class="fa-solid fa-file-export"></i> Exportar datos (JSON)
                        </a>
                    @endcan
                    @can('padres.eliminar')
                        <form action="{{ route('padres.revocar', $padre) }}" method="POST"
                              class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto"
                              data-confirm="¿Revocar el consentimiento y enviar el expediente a la papelera?">
                            @csrf
                            <input type="text" name="motivo" class="input flex-1" required maxlength="500"
                                   placeholder="Motivo de la revocación">
                            <button type="submit" class="btn-danger">
                                <i class="fa-solid fa-user-shield"></i> Revocar consentimiento
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            <a href="{{ route('padres.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-brand-600 transition-colors cursor-pointer">
                <i class="fa-solid fa-arrow-left"></i> Volver al listado
            </a>
        </div>
    </div>
@if(request()->boolean('wallet_pending') && ! $padre->wallet_share_url)
    <script>
        (() => {
            const storageKey = 'wallet-poll-{{ $padre->id }}';
            const attempts = Number(sessionStorage.getItem(storageKey) || 0);
            if (attempts < 12) {
                sessionStorage.setItem(storageKey, String(attempts + 1));
                setTimeout(() => window.location.reload(), 5000);
            } else {
                sessionStorage.removeItem(storageKey);
                window.addEventListener('DOMContentLoaded', () => {
                    window.showAlert('Wallet sigue pendiente. Revisa los logs del worker en Render.', true);
                });
            }
        })();
    </script>
@elseif($padre->wallet_share_url)
    <script>sessionStorage.removeItem('wallet-poll-{{ $padre->id }}');</script>
@endif
</x-app-layout>
