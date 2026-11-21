<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-children text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Editar Hijo') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                    Catálogo · Actualizar Registro
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card overflow-hidden">

            {{-- Header Superior --}}
            <div class="p-8 border-b border-slate-100">
                <div class="flex items-center gap-4">
                    <div class="h-14 w-14 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-xl font-extrabold" aria-hidden="true">
                        {{ mb_substr($hijo->nombre, 0, 1) }}
                    </div>
                    <div>
                        <h3 class="font-heading text-xl text-slate-900">Modificar Datos del Niño</h3>
                        <p class="text-sm font-semibold text-slate-500">Actualiza la información del registro.</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('hijos.update', $hijo->id) }}" method="POST" class="p-8 space-y-6">
                @csrf
                @method('PUT')

                @include('partials.duplicados')

                <div>
                    <label for="nombre" class="label">Nombre del Niño</label>
                    <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $hijo->nombre) }}"
                           class="input" placeholder="Ej: Mateo Sanchez" required autofocus>
                    @error('nombre')
                        <p class="mt-2 text-sm font-bold text-brand-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="padre_id" class="label">Padre / Responsable</label>
                        <select id="padre_id" name="padre_id" class="input" required>
                            <option value="">-- Selecciona un responsable --</option>
                            @foreach ($padres as $padre)
                                <option value="{{ $padre->id }}" {{ $hijo->padre_id == $padre->id ? 'selected' : '' }}>
                                    {{ $padre->nombre }}
                                </option>
                            @endforeach
                        </select>
                        @error('padre_id')
                            <p class="mt-2 text-sm font-bold text-brand-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="fecha_nacimiento" class="label">Fecha de Nacimiento</label>
                        <input id="fecha_nacimiento" type="date" name="fecha_nacimiento"
                               value="{{ old('fecha_nacimiento', $hijo->fecha_nacimiento ? $hijo->fecha_nacimiento->format('Y-m-d') : date('Y-m-d')) }}"
                               max="{{ now()->subDay()->toDateString() }}" class="input" required>
                        @error('fecha_nacimiento')
                            <p class="mt-2 text-sm font-bold text-brand-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Footer de Acciones --}}
                <div class="flex items-center justify-between pt-8 border-t border-slate-100">
                    <a href="{{ route('hijos.index') }}" class="text-xs font-black text-slate-400 hover:text-slate-600 transition uppercase tracking-widest">
                        Cancelar
                    </a>
                    <button type="submit" class="btn-primary px-8 py-3">
                        <i class="fa-solid fa-rotate"></i>
                        ACTUALIZAR DATOS
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
