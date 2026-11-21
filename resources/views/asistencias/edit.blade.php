<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                    <i class="fa-solid fa-pen-to-square text-xl"></i>
                </div>
                <div>
                    <h2 class="page-title leading-tight">Corregir asistencia</h2>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                        {{ $asistencia->servicio?->descripcion_servicio ?? 'Servicio' }} · {{ $asistencia->formated_fecha }}
                    </p>
                </div>
            </div>
            @if($asistencia->padre)
                <a href="{{ route('padres.show', $asistencia->padre->id) }}" class="btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i> Volver al expediente
                </a>
            @endif
        </div>
    </x-slot>

    <div class="space-y-6 max-w-4xl mx-auto">
        <x-validation-errors class="mb-4" />

        <div class="card overflow-hidden">
            <div class="p-8 border-b border-slate-100">
                <h3 class="font-heading text-xl text-slate-900">Registros por niño</h3>
                <p class="mt-1 text-sm font-semibold text-slate-500">
                    Cada cambio queda firmado con tu usuario y requiere un motivo. La bitácora no se puede editar.
                </p>
            </div>

            <form method="POST" action="{{ route('asistencias.update', $asistencia) }}" class="p-8 space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    @foreach($asistencia->registros as $registro)
                        <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                                <div>
                                    <p class="font-extrabold text-slate-800">{{ $registro->nombre }}</p>
                                    <p class="text-xs font-semibold text-slate-400">
                                        Registro #{{ $registro->id }}
                                        @if($registro->hijo_id) · Hijo #{{ $registro->hijo_id }} @endif
                                    </p>
                                </div>
                                @if($registro->corregidoPor)
                                    <span class="badge-neutral">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                        Corregido por {{ $registro->corregidoPor->name }}
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="label" for="estado-{{ $registro->id }}">Estado</label>
                                    <select id="estado-{{ $registro->id }}"
                                            name="registros[{{ $registro->id }}][estado]"
                                            class="input" required>
                                        <option value="asistio" @selected(old('registros.'.$registro->id.'.estado', $registro->estado) === 'asistio')>Asistió</option>
                                        <option value="no_asistio" @selected(old('registros.'.$registro->id.'.estado', $registro->estado) === 'no_asistio')>No asistió</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="label" for="ficha-{{ $registro->id }}">Ficha</label>
                                    <input id="ficha-{{ $registro->id }}"
                                           type="text"
                                           name="registros[{{ $registro->id }}][ficha]"
                                           value="{{ old('registros.'.$registro->id.'.ficha', $registro->ficha) }}"
                                           class="input" maxlength="50" placeholder="Sin ficha">
                                </div>
                                <div>
                                    <label class="label" for="salida-{{ $registro->id }}">Salida</label>
                                    <input id="salida-{{ $registro->id }}"
                                           type="datetime-local"
                                           name="registros[{{ $registro->id }}][salida_at]"
                                           value="{{ old('registros.'.$registro->id.'.salida_at', $registro->salida_at?->format('Y-m-d\TH:i')) }}"
                                           class="input">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div>
                    <label class="label" for="motivo">Motivo de la corrección *</label>
                    <textarea id="motivo" name="motivo" class="input" rows="3" required maxlength="500"
                              placeholder="Ej. Se marcó salida por error, la ficha correcta es 014...">{{ old('motivo') }}</textarea>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar corrección
                    </button>
                    <a href="{{ $asistencia->padre ? route('padres.show', $asistencia->padre->id) : route('reportes.index') }}"
                       class="btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>

        @can('asistencias.eliminar')
            <div class="card p-8 border border-rose-100">
                <h3 class="font-heading text-lg text-rose-700">Anular asistencia</h3>
                <p class="mt-1 text-sm font-semibold text-slate-500">
                    La asistencia dejará de aparecer en reportes, pero se conserva en la bitácora.
                </p>
                <form method="POST" action="{{ route('asistencias.destroy', $asistencia) }}" class="mt-5 flex flex-col sm:flex-row gap-3"
                      data-confirm="¿Anular esta asistencia? Esta acción queda auditada.">
                    @csrf
                    @method('DELETE')
                    <input type="text" name="motivo" class="input flex-1" required maxlength="500" placeholder="Motivo de la anulación">
                    <button type="submit" class="btn-danger">
                        <i class="fa-solid fa-ban"></i> Anular
                    </button>
                </form>
            </div>
        @endcan

        <div class="card overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100">
                <h3 class="font-heading text-xl text-slate-900">Bitácora de cambios</h3>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($asistencia->cambios as $cambio)
                    <div class="px-8 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-extrabold text-slate-800">
                                {{ ucfirst($cambio->accion) }}
                                @if($cambio->user) · {{ $cambio->user->name }} @endif
                            </p>
                            @if($cambio->motivo)
                                <p class="text-xs font-semibold text-slate-500">{{ $cambio->motivo }}</p>
                            @endif
                        </div>
                        <span class="text-xs font-bold text-slate-400">{{ $cambio->created_at?->format('d/m/Y H:i') }}</span>
                    </div>
                @empty
                    <p class="px-8 py-8 text-sm font-semibold text-slate-400">Sin cambios registrados.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
