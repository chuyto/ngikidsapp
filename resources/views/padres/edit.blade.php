<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-user-pen text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Editar Padre') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                    Administración · Actualizar Registro
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto">
        <form action="{{ route('padres.update', $padre->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="mb-8 flex items-start gap-3 bg-brand-50 border border-brand-200 text-brand-700 px-6 py-4 rounded-2xl" role="alert">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div>
                        <strong class="font-extrabold uppercase text-sm block mb-1">Revisa los datos:</strong>
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            @include('partials.duplicados')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {{-- Columna Izquierda: Formularios --}}
                <div class="lg:col-span-2 space-y-8">

                    {{-- Información del Padre --}}
                    <div class="card p-8">
                        <h3 class="section-title flex items-center gap-3 mb-6">
                            <i class="fa-solid fa-address-card text-brand-500"></i> Información del Padre
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-1 md:col-span-2">
                                <x-label for="nombre" value="Nombre Completo" />
                                <x-input id="nombre" name="nombre" type="text" value="{{ old('nombre', $padre->nombre) }}" required />
                            </div>

                            <div class="col-span-1 md:col-span-2">
                                <x-label for="parentesco" value="Parentesco / Relación" />
                                <select name="parentesco" id="parentesco" class="input">
                                    <option value="">Seleccione el parentesco</option>
                                    <option value="Padre" {{ (old('parentesco', $padre->parentesco) == 'Padre') ? 'selected' : '' }}>Padre</option>
                                    <option value="Madre" {{ (old('parentesco', $padre->parentesco) == 'Madre') ? 'selected' : '' }}>Madre</option>
                                    <option value="Abuelo/a" {{ (old('parentesco', $padre->parentesco) == 'Abuelo/a') ? 'selected' : '' }}>Abuelo/a</option>
                                    <option value="Tío/a" {{ (old('parentesco', $padre->parentesco) == 'Tío/a') ? 'selected' : '' }}>Tío/a</option>
                                    <option value="Tutor Legal" {{ (old('parentesco', $padre->parentesco) == 'Tutor Legal') ? 'selected' : '' }}>Tutor Legal</option>
                                    <option value="Otro" {{ (old('parentesco', $padre->parentesco) == 'Otro') ? 'selected' : '' }}>Otro</option>
                                </select>
                            </div>

                            {{-- Red Ministerial --}}
                            <div>
                                <x-label for="red_id" value="Red Ministerial" />
                                <select name="red_id" id="red_id" class="input">
                                    @foreach($redes as $red)
                                        <option value="{{ $red->id }}" {{ (old('red_id', $padre->red_id) == $red->id) ? 'selected' : '' }}>{{ $red->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Teléfono --}}
                            <div>
                                <x-label for="telefono" value="Teléfono" />
                                <div class="relative">
                                    <i class="fa-solid fa-phone text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                                    <x-input id="telefono" name="telefono" type="tel" class="pl-11" value="{{ old('telefono', $padre->telefono) }}" />
                                </div>
                            </div>

                            {{-- Correo Electrónico --}}
                            <div class="col-span-1 md:col-span-2">
                                <x-label for="email" value="Correo Electrónico (Opcional)" />
                                <div class="relative">
                                    <i class="fa-solid fa-envelope text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                                    <x-input id="email" name="email" type="email" class="pl-11" value="{{ old('email', $padre->email) }}" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Información de los Hijos --}}
                    <div class="card p-8">
                        <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
                            <h3 class="section-title flex items-center gap-3">
                                <i class="fa-solid fa-children text-emerald-500"></i> Hijos Vinculados
                            </h3>
                            <button type="button" id="add-child" class="btn-secondary px-4 py-2 text-xs">
                                <i class="fa-solid fa-plus"></i> Agregar hijo
                            </button>
                        </div>

                        <div id="hijos-container" class="space-y-4">
                            @foreach ($padre->hijos as $index => $hijo)
                                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 grid grid-cols-1 md:grid-cols-3 gap-6 relative row-hijo">
                                    <input type="hidden" name="hijos[{{ $index }}][id]" value="{{ $hijo->id }}">
                                    <div class="md:col-span-2">
                                        <label class="label">Nombre del Hijo</label>
                                        <input type="text" name="hijos[{{ $index }}][nombre]" value="{{ old('hijos.'.$index.'.nombre', $hijo->nombre) }}" class="input" required>
                                    </div>
                                    <div>
                                        <label class="label">Fecha de Nacimiento</label>
                                        <input type="date" name="hijos[{{ $index }}][fecha_nacimiento]" value="{{ old('hijos.'.$index.'.fecha_nacimiento', $hijo->fecha_nacimiento ? \Carbon\Carbon::parse($hijo->fecha_nacimiento)->format('Y-m-d') : '') }}" class="input fecha-nacimiento" required>
                                    </div>

                                    <div class="md:col-span-3">
                                        @php
                                            $edad = $hijo->edad ?? 0;
                                        @endphp
                                        <p class="text-brand-600 font-bold text-sm edad-visual">
                                            <i class="fa-solid fa-cake-candles mr-1.5"></i>Edad: {{ $edad }} {{ $edad === 1 ? 'año' : 'años' }}
                                        </p>
                                    </div>

                                    <button type="button" aria-label="Quitar hijo"
                                            class="absolute top-4 right-4 w-9 h-9 flex items-center justify-center rounded-full bg-white border border-slate-200 text-brand-500 shadow-sm hover:bg-brand-50 cursor-pointer transition-colors eliminar-hijo">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Columna Derecha: Foto y Acción --}}
                <div class="lg:col-span-1 space-y-6">
                    <div class="card p-8 lg:sticky lg:top-24">
                        <h3 class="section-title mb-6 flex items-center gap-3">
                            <i class="fa-solid fa-image text-emerald-500"></i> Fotografía
                        </h3>

                        <div class="flex flex-col items-center space-y-6">
                            <div class="relative w-full aspect-square rounded-2xl overflow-hidden border-2 border-slate-200 bg-slate-50 flex items-center justify-center">
                                @if ($padre->foto_padre)
                                    <img src="{{ $padre->foto_padre }}" alt="Foto actual de {{ $padre->nombre }}" class="w-full h-full object-cover">
                                @else
                                    <div class="text-slate-400 text-center p-4">
                                        <i class="fa-solid fa-camera text-5xl mb-3 opacity-30"></i>
                                        <p class="text-xs font-bold uppercase tracking-widest">Sin foto registrada</p>
                                    </div>
                                @endif
                            </div>

                            <div class="w-full">
                                <x-label for="foto_padre" value="Actualizar Foto (Opcional)" />
                                <input type="file" name="foto_padre" id="foto_padre" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-extrabold file:bg-brand-600 file:text-white hover:file:bg-brand-500 cursor-pointer file:cursor-pointer transition-colors">
                            </div>
                        </div>

                        <div class="mt-10 pt-8 border-t border-slate-100 space-y-3">
                            <button type="submit" class="btn-primary w-full py-4">
                                <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                            </button>
                            <a href="{{ route('padres.index') }}" class="btn-ghost w-full">
                                <i class="fa-solid fa-xmark"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            function calcularEdad(fecha) {
                if (!fecha) return '';
                const nacimiento = new Date(fecha + 'T00:00:00');
                const hoy = new Date();
                let edad = hoy.getFullYear() - nacimiento.getFullYear();
                const m = hoy.getMonth() - nacimiento.getMonth();
                if (m < 0 || (m === 0 && hoy.getDate() < nacimiento.getDate())) {
                    edad--;
                }
                return Math.max(0, edad);
            }

            function attachChildEvents(childDiv) {
                const fechaInput = childDiv.querySelector('.fecha-nacimiento');
                const edadTexto = childDiv.querySelector('.edad-visual');
                const btnEliminar = childDiv.querySelector('.eliminar-hijo');

                if (fechaInput && edadTexto) {
                    fechaInput.addEventListener('change', function () {
                        if (this.value) {
                            const edad = calcularEdad(this.value);
                            edadTexto.textContent = "Edad: " + edad + (edad === 1 ? " año" : " años");
                            edadTexto.classList.remove('hidden');
                        } else {
                            edadTexto.classList.add('hidden');
                        }
                    });
                }

                if (btnEliminar) {
                    btnEliminar.addEventListener('click', function () {
                        childDiv.remove();
                    });
                }
            }

            document.querySelectorAll('.row-hijo').forEach(attachChildEvents);

            let hijoIndex = {{ count($padre->hijos) }};
            document.getElementById('add-child').addEventListener('click', function () {
                const container = document.getElementById('hijos-container');
                const childFields = `
                    <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 grid grid-cols-1 md:grid-cols-3 gap-6 relative row-hijo">
                        <div class="md:col-span-2">
                            <label class="label">Nombre del Nuevo Hijo</label>
                            <input type="text" name="hijos[${hijoIndex}][nombre]" class="input" required>
                        </div>
                        <div>
                            <label class="label">Fecha de Nacimiento</label>
                            <input type="date" name="hijos[${hijoIndex}][fecha_nacimiento]" class="input fecha-nacimiento" required>
                        </div>
                        <div class="md:col-span-3">
                            <p class="text-brand-600 font-bold text-sm edad-visual hidden"></p>
                        </div>
                        <button type="button" aria-label="Quitar hijo"
                                class="absolute top-4 right-4 w-9 h-9 flex items-center justify-center rounded-full bg-white border border-slate-200 text-brand-500 shadow-sm hover:bg-brand-50 cursor-pointer transition-colors eliminar-hijo">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                `;

                container.insertAdjacentHTML('beforeend', childFields);
                attachChildEvents(container.lastElementChild);
                hijoIndex++;
            });
        });
    </script>
</x-app-layout>