<form action="{{ route('servicios.store') }}" method="POST" class="space-y-6" data-modal-form>
    @csrf

    {{-- Descripción --}}
    <div>
        <label for="descripcion_servicio" class="label">Descripción del Servicio</label>
        <div class="relative">
            <i class="fa-solid fa-tag text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
            <input id="descripcion_servicio" type="text" name="descripcion_servicio" class="input pl-11" placeholder="Ej: Servicio General" required>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Horario --}}
        <div>
            <label for="horario_input" class="label">Horario</label>
            <input id="horario_input" list="horario_list" type="text" name="horario_servicio"
                   class="input cursor-pointer" placeholder="Seleccionar o escribir..." required>
            <datalist id="horario_list">
                <option value="8:00 AM"></option>
                <option value="10:30 AM"></option>
                <option value="1:00 PM"></option>
            </datalist>
        </div>
        {{-- Fecha --}}
        <div>
            <label for="fecha_servicio" class="label">Fecha</label>
            <input id="fecha_servicio" type="date" name="fecha_servicio" value="{{ date('Y-m-d') }}" class="input" required>
        </div>
    </div>

    {{-- SUPERVISORES MULTISELECT (custom con Alpine) --}}
    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200"
         x-data="{
             open: false,
             selected: [],
             query: '',
             options: {{ Js::from($supervisores->map(fn ($s) => ['id' => $s->id, 'nombre' => $s->nombre])->values()->all()) }},
             isSelected(id) { return this.selected.includes(id); },
             get filtered() {
                 const q = this.query.trim().toLowerCase();
                 if (!q) return this.options;
                 return this.options.filter(s => s.nombre.toLowerCase().includes(q));
             },
             toggle(id) {
                 const i = this.selected.indexOf(id);
                 if (i === -1) { this.selected.push(id); } else { this.selected.splice(i, 1); }
             },
             nombre(id) {
                 const s = this.options.find(s => s.id === id);
                 return s ? s.nombre : '';
             },
         }">
        <label class="label flex items-center gap-2">
            <i class="fa-solid fa-user-shield text-brand-500"></i> Supervisores Responsables
        </label>

        {{-- Campo con chips: refleja los ids en inputs ocultos para el backend --}}
        <template x-for="id in selected" :key="'sup-' + id">
            <input type="hidden" name="supervisores[]" :value="id">
        </template>

        <div class="relative" @click.outside="open = false" @keydown.escape.window="open = false">

            {{-- Control de selección --}}
            <button type="button"
                    @click="open = !open"
                    :class="open ? 'border-brand-300 ring-4 ring-brand-100' : 'border-white/70'"
                    class="w-full min-h-[3.5rem] flex flex-wrap items-center gap-2 rounded-2xl bg-white/70 backdrop-blur-md border px-4 py-3 text-left shadow-sm transition-all duration-200 focus:outline-none cursor-pointer">

                <template x-if="selected.length === 0">
                    <span class="text-slate-400 text-sm font-semibold">Selecciona los supervisores...</span>
                </template>

                <template x-for="id in selected" :key="'chip-' + id">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 text-brand-700 border border-brand-200 px-3 py-1 text-xs font-bold shadow-sm">
                        <i class="fa-solid fa-user-check text-[10px]"></i>
                        <span x-text="nombre(id)"></span>
                        <button type="button"
                                @click.stop="toggle(id)"
                                class="text-brand-400 hover:text-brand-700 transition-colors cursor-pointer ml-0.5"
                                :aria-label="'Quitar ' + nombre(id)">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </span>
                </template>

                <span class="ml-auto text-slate-400 pointer-events-none">
                    <i class="fa-solid fa-chevron-down text-xs"></i>
                </span>
            </button>

            {{-- Dropdown con la lista --}}
            <div x-cloak
                 x-show="open"
                 x-transition.opacity.duration.150ms
                 class="absolute left-0 right-0 top-full mt-2 z-50 max-h-72 overflow-y-auto rounded-2xl bg-white border border-slate-200 shadow-[0_24px_60px_-20px_rgba(30,25,24,0.3)] p-2">

                {{-- Buscador --}}
                <div class="relative mb-2 sticky top-0 bg-white pt-1 pb-2 border-b border-slate-100">
                    <i class="fa-solid fa-magnifying-glass text-sm text-slate-400 absolute left-3.5 top-1/2 translate-y-[-55%]"></i>
                    <input type="text"
                           x-model="query"
                           placeholder="Buscar supervisor..."
                           class="w-full rounded-xl border border-slate-200 bg-slate-50/60 pl-9 pr-3 py-2 text-sm font-semibold text-slate-700 placeholder:text-slate-400 focus:border-brand-300 focus:ring-4 focus:ring-brand-100 outline-none transition-all duration-200">
                </div>

                <template x-if="filtered.length === 0">
                    <p class="text-sm font-semibold text-slate-400 text-center py-4">Sin resultados para la búsqueda.</p>
                </template>

                <template x-for="s in filtered" :key="'opt-' + s.id">
                    <button type="button"
                            @click="toggle(s.id)"
                            :class="isSelected(s.id) ? 'bg-brand-50 text-brand-700 border-brand-200' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-transparent'"
                            class="w-full flex items-center gap-3 rounded-xl border px-3 py-2.5 text-sm font-semibold transition-colors duration-150 cursor-pointer">
                        <span :class="isSelected(s.id) ? 'bg-brand-600 text-white border-brand-600' : 'bg-white border-slate-300'"
                              class="w-5 h-5 shrink-0 rounded-md border flex items-center justify-center transition-colors">
                            <i x-show="isSelected(s.id)" class="fa-solid fa-check text-[10px]"></i>
                        </span>
                        <span x-text="s.nombre"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Activo --}}
    <div class="bg-slate-50 p-6 rounded-2xl border border-slate-200">
        <label class="label flex items-center gap-2">
            <i class="fa-solid fa-power-off text-emerald-500"></i> ¿Activar ahora?
        </label>
        <select name="activo" class="input">
            <option value="1">Sí, activar</option>
            <option value="0">No, guardar inactivo</option>
        </select>
    </div>

    <div class="pt-6 border-t border-slate-100">
        <button type="submit" class="btn-primary w-full">
            <i class="fa-solid fa-floppy-disk"></i> Registrar Servicio
        </button>
    </div>
</form>