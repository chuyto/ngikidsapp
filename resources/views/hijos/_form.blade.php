<form action="{{ route('hijos.store') }}" method="POST" class="space-y-8" data-modal-form>
    @csrf

    @include('partials.duplicados')

    <div>
        <label for="nombre" class="label">Nombre del Niño</label>
        <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}"
               class="input" placeholder="Ej: Mateo Sanchez" required autofocus>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label for="padre_id" class="label">Padre / Responsable</label>
            <select id="padre_id" name="padre_id" class="input" required>
                <option value="">-- Selecciona un responsable --</option>
                @foreach ($padres as $padre)
                    <option value="{{ $padre->id }}" {{ old('padre_id') == $padre->id ? 'selected' : '' }}>
                        {{ $padre->nombre }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="fecha_nacimiento" class="label">Fecha de Nacimiento</label>
            <input id="fecha_nacimiento" type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}"
                   max="{{ now()->subDay()->toDateString() }}" class="input" required aria-describedby="fecha_nacimiento_help">
            <p id="fecha_nacimiento_help" class="mt-2 text-xs font-semibold text-slate-400">Debe ser una fecha anterior a hoy.</p>
        </div>
    </div>

    <div class="pt-6 border-t border-slate-100">
        <button type="submit" class="btn-primary w-full py-3">
            <i class="fa-solid fa-check"></i>
            CREAR HIJO
        </button>
    </div>
</form>
