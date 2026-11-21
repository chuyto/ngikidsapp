<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-pen-to-square text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Editar Servicio') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">Catálogos · Eventos</p>
            </div>
        </div>
    </x-slot>

    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <style>
        .ts-control { background: #fff !important; border: 1px solid #e2e8f0 !important; color: #1e293b !important; border-radius: 1rem !important; padding: 0.65rem 1rem !important; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.04) !important; }
        .ts-dropdown { background: #fff !important; color: #334155 !important; border: 1px solid #e2e8f0 !important; border-radius: 1rem !important; box-shadow: 0 16px 40px -16px rgba(30,25,24,0.2) !important; }
        .ts-dropdown .active { background: #ffe4e6 !important; color: #9f1239 !important; }
        .ts-control .item { background: #ffe4e6 !important; color: #9f1239 !important; border-radius: 0.5rem !important; font-weight: 700; }
        .ts-dropdown .option { color: #475569; font-weight: 600; }
        .ts-wrapper.multi .ts-control > div > .remove { color: #9f1239 !important; }
    </style>

    <div class="max-w-3xl mx-auto">
        <div class="card overflow-hidden">
            <form action="{{ route('servicios.update', $servicio->id) }}" method="POST" class="p-8 space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="descripcion_servicio" class="label">Descripción</label>
                    <div class="relative">
                        <i class="fa-solid fa-tag text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                        <input id="descripcion_servicio" type="text" name="descripcion_servicio" value="{{ $servicio->descripcion_servicio }}" class="input pl-11" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="horario_input" class="label">Horario</label>
                        <input id="horario_input" list="horario_list" type="text" name="horario_servicio"
                               value="{{ $servicio->horario_servicio }}" class="input cursor-pointer" required>
                        <datalist id="horario_list">
                            <option value="8:00 AM"></option>
                            <option value="10:30 AM"></option>
                            <option value="1:00 PM"></option>
                        </datalist>
                    </div>
                    <div>
                        <label for="fecha_servicio" class="label">Fecha</label>
                        <input id="fecha_servicio" type="date" name="fecha_servicio" value="{{ $servicio->fecha_servicio }}" class="input" required>
                    </div>
                </div>

                {{-- SUPERVISORES MULTISELECT EDIT --}}
                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                    <label for="select-supervisores" class="label flex items-center gap-2">
                        <i class="fa-solid fa-user-shield text-brand-500"></i> Supervisores Asignados
                    </label>
                    <select id="select-supervisores" name="supervisores[]" multiple placeholder="Buscar..." autocomplete="off">
                        @foreach($supervisores as $sup)
                            <option value="{{ $sup->id }}" {{ in_array($sup->id, $supervisoresAsignados) ? 'selected' : '' }}>
                                {{ $sup->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
                    <label class="label flex items-center gap-2">
                        <i class="fa-solid fa-power-off text-emerald-500"></i> Estado
                    </label>
                    <select name="activo" class="input">
                        <option value="1" {{ $servicio->activo ? 'selected' : '' }}>Activo</option>
                        <option value="0" {{ !$servicio->activo ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>

                <div class="pt-6 border-t border-slate-100 flex justify-between items-center">
                    <a href="{{ route('servicios.index') }}" class="btn-ghost">Cancelar</a>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Actualizar Servicio
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <script>
        new TomSelect("#select-supervisores", { plugins: ['remove_button'], create: false });
    </script>
</x-app-layout>