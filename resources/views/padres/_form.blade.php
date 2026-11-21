<form action="{{ route('padres.store') }}" method="POST" data-modal-form>
    @csrf

    @include('partials.duplicados')

    <div id="formErrors" class="hidden mb-6 flex items-start gap-3 bg-brand-50 border border-brand-200 text-brand-700 px-6 py-4 rounded-2xl" role="alert">
        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
        <div>
            <strong class="font-extrabold uppercase text-sm block mb-1">Revisa los siguientes datos:</strong>
            <ul class="list-disc pl-5 text-sm space-y-1"></ul>
        </div>
    </div>

    <div class="space-y-8">
        {{-- Información Principal --}}
        <div class="card p-6">
            <h3 class="section-title flex items-center gap-3 mb-6">
                <i class="fa-solid fa-address-card text-brand-500"></i> Información del Padre
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="col-span-1 md:col-span-2">
                    <x-label for="nombre" value="Nombre Completo" />
                    <x-input id="nombre" name="nombre" type="text" required value="{{ old('nombre') }}" placeholder="Nombre completo del responsable" />
                </div>
                <div class="col-span-1 md:col-span-2">
                    <x-label for="parentesco" value="Parentesco" />
                    <select name="parentesco" id="parentesco" class="input">
                        <option value="">Seleccione...</option>
                        @foreach(['Padre', 'Madre', 'Abuelo/a', 'Tío/a', 'Tutor Legal'] as $p)
                            <option value="{{ $p }}" {{ old('parentesco') == $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-label for="red_id" value="Red Ministerial" />
                    <select name="red_id" id="red_id" class="input">
                        <option value="">Seleccione...</option>
                        @foreach($redes as $red)
                            <option value="{{ $red->id }}" {{ old('red_id') == $red->id ? 'selected' : '' }}>{{ $red->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-label for="telefono" value="Teléfono" />
                    <div class="relative">
                        <i class="fa-solid fa-phone text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                        <x-input id="telefono" name="telefono" type="tel" class="pl-11" value="{{ old('telefono') }}" placeholder="10 dígitos" />
                    </div>
                </div>
                <div class="col-span-1 md:col-span-2">
                    <x-label for="email" value="Correo Electrónico (Opcional)" />
                    <div class="relative">
                        <i class="fa-solid fa-envelope text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                        <x-input id="email" name="email" type="email" class="pl-11" value="{{ old('email') }}" placeholder="Recibirá su credencial por correo" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Sección de Hijos --}}
        <div class="card p-6">
            <div class="flex justify-between items-center mb-6 flex-wrap gap-4">
                <h3 class="section-title flex items-center gap-3">
                    <i class="fa-solid fa-children text-emerald-500"></i> Información de los Hijos
                </h3>
                <button type="button" data-add-child class="btn-secondary px-4 py-2 text-xs">
                    <i class="fa-solid fa-plus"></i> Agregar hijo
                </button>
            </div>
            <div class="space-y-4" data-children-container data-next-index="1">
                <div class="p-6 bg-slate-50 rounded-2xl border border-slate-200 grid grid-cols-1 md:grid-cols-3 gap-6 relative" data-child-row>
                    <div class="md:col-span-2">
                        <label class="label" for="child-name-0">Nombre del hijo 1</label>
                        <input id="child-name-0" type="text" name="hijos[0][nombre]" class="input" placeholder="Nombre completo" required>
                    </div>
                    <div>
                        <label class="label" for="child-birthdate-0">Fecha de nacimiento</label>
                        <input id="child-birthdate-0" type="date" name="hijos[0][fecha_nacimiento]"
                               max="{{ now()->subDay()->toDateString() }}" class="input" required>
                    </div>
                </div>
            </div>
            <p class="text-sm text-slate-400 font-semibold mt-4">
                <i class="fa-solid fa-circle-info mr-1.5 text-brand-400"></i>La edad se calcula automáticamente a partir de la fecha de nacimiento.
            </p>
        </div>

        {{-- Consentimiento de privacidad --}}
        <div class="card p-6">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="privacidad" value="1" class="mt-1 accent-brand-600" {{ old('privacidad') ? 'checked' : '' }}>
                <span class="text-sm font-semibold text-slate-600">
                    El padre/madre o tutor acepta el
                    <a href="{{ route('privacidad') }}" target="_blank" class="font-extrabold text-brand-600 hover:underline">Aviso de Privacidad</a>
                    para el tratamiento de sus datos y los de sus hijos con fines de registro, control de asistencia y credencial digital.
                </span>
            </label>
            @error('privacidad')
                <p class="mt-2 text-sm font-bold text-brand-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Botón de Envío --}}
        <div class="pt-2">
            <button type="submit" class="btn-primary w-full py-4 text-base">
                <i class="fa-solid fa-check"></i> Finalizar y Guardar Registro
            </button>
        </div>
    </div>
</form>
