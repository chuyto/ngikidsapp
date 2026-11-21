@php
    $supervisor = $supervisor ?? null;
    $editando = $supervisor !== null;
@endphp

<form action="{{ $editando ? route('supervisores.update', $supervisor) : route('supervisores.store') }}" method="POST" class="space-y-8" data-modal-form>
    @csrf
    @if($editando)
        @method('PUT')
    @endif

    <div>
        <label for="nombre-{{ $supervisor?->id ?? 'nuevo' }}" class="label">Nombre del Supervisor</label>
        <input id="nombre-{{ $supervisor?->id ?? 'nuevo' }}" type="text" name="nombre"
               class="input"
               value="{{ old('nombre', $supervisor->nombre ?? '') }}"
               placeholder="Nombre completo del supervisor" required autofocus>
    </div>

    <div class="pt-6 border-t border-slate-100">
        <button type="submit" class="btn-primary w-full py-3">
            <i class="fa-solid fa-check"></i>
            {{ $editando ? 'GUARDAR CAMBIOS' : 'REGISTRAR SUPERVISOR' }}
        </button>
    </div>
</form>
