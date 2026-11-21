@php
    $dupPadres = session('duplicados')['padres'] ?? [];
    $dupHijos = session('duplicados')['hijos'] ?? [];
@endphp
@if (count($dupPadres) > 0 || count($dupHijos) > 0)
    <div class="mb-6 border-2 border-amber-300 bg-amber-50 rounded-2xl p-6" role="alert">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation text-amber-500 text-xl mt-0.5"></i>
            <div class="flex-1 min-w-0">
                <strong class="font-extrabold uppercase text-sm text-amber-800 block mb-1">Posible registro duplicado</strong>
                <p class="text-sm text-amber-700 font-semibold mb-4">
                    Encontramos coincidencias con registros existentes. Revísalas antes de guardar para evitar duplicar datos.
                    Si confirmas que es una persona diferente, marca la casilla y guarda de nuevo.
                </p>

                @if (count($dupPadres) > 0)
                    <p class="text-xs font-black uppercase tracking-widest text-amber-600 mb-2">Padres parecidos ({{ count($dupPadres) }})</p>
                    <ul class="space-y-2 mb-4">
                        @foreach ($dupPadres as $dup)
                            <li class="bg-white border border-amber-200 rounded-xl px-4 py-3 text-sm">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <span class="font-extrabold text-slate-800">{{ $dup['nombre'] }}</span>
                                    @if (! empty($dup['eliminado']))
                                        <a href="{{ route('padres.index', ['papelera' => 1]) }}" class="text-xs font-black text-slate-500 hover:underline uppercase tracking-widest">
                                            En papelera · restaurar <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @else
                                        <a href="{{ route('padres.show', $dup['id']) }}" class="text-xs font-black text-brand-600 hover:underline uppercase tracking-widest">
                                            Ver {{ $dup['uuid_short'] }} <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach ($dup['motivos'] as $motivo)
                                        <span class="text-[11px] font-black uppercase tracking-wider bg-amber-100 text-amber-700 rounded-full px-2.5 py-0.5">{{ $motivo }}</span>
                                    @endforeach
                                </div>
                                <p class="text-xs text-slate-500 font-semibold mt-1.5">
                                    Tel: {{ $dup['telefono'] ?: '—' }}
                                    @if ($dup['email']) · {{ $dup['email'] }} @endif
                                    @if ($dup['red']) · Red: {{ $dup['red'] }} @endif
                                    @if (count($dup['hijos']) > 0) · Hijos: {{ implode(', ', $dup['hijos']) }} @endif
                                    @if (! empty($dup['eliminado']))
                                        · <span class="font-black text-slate-500 uppercase">En papelera: restaura en vez de duplicar</span>
                                    @endif
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (count($dupHijos) > 0)
                    <p class="text-xs font-black uppercase tracking-widest text-amber-600 mb-2">Niños ya registrados ({{ count($dupHijos) }})</p>
                    <ul class="space-y-2 mb-4">
                        @foreach ($dupHijos as $dup)
                            <li class="bg-white border border-amber-200 rounded-xl px-4 py-3 text-sm">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <span class="font-extrabold text-slate-800">{{ $dup['nombre'] }} <span class="font-semibold text-slate-400">· {{ $dup['fecha_nacimiento'] }}</span></span>
                                    @if (! empty($dup['eliminado']))
                                        <a href="{{ route('hijos.index', ['papelera' => 1]) }}" class="text-xs font-black text-slate-500 hover:underline uppercase tracking-widest">
                                            En papelera · restaurar <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @elseif (! empty($dup['padre_eliminado']))
                                        <a href="{{ route('padres.index', ['papelera' => 1]) }}" class="text-xs font-black text-slate-500 hover:underline uppercase tracking-widest">
                                            Padre en papelera · restaurar <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @elseif ($dup['padre_id'])
                                        <a href="{{ route('padres.show', $dup['padre_id']) }}" class="text-xs font-black text-brand-600 hover:underline uppercase tracking-widest">
                                            Ver padre <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 font-semibold mt-1">
                                    Registrado con: {{ $dup['padre_nombre'] ?? '—' }} ({{ $dup['padre_uuid'] ?? '—' }})
                                </p>
                            </li>
                        @endforeach
                    </ul>
                @endif

                <label class="mt-2 flex items-start gap-2.5 cursor-pointer bg-white border border-amber-300 rounded-xl px-4 py-3">
                    <input type="checkbox" name="confirmar_duplicado" value="1" class="mt-1 accent-amber-600">
                    <span class="text-sm font-bold text-slate-700">Entiendo, es un registro diferente. Crear de todos modos.</span>
                </label>
            </div>
        </div>
    </div>
@endif
