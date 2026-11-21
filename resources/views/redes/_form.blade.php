<form action="{{ route('redes.store') }}" method="POST" class="space-y-8" data-modal-form>
    @csrf

    <div>
        <label for="descripcion" class="label">Nombre de la Red</label>
        <input id="descripcion" type="text" name="descripcion"
            class="input"
            placeholder="Ej: Elyon, Daniel, Paulus." required autofocus>
        <p class="mt-2 text-xs font-semibold text-slate-400 italic">
            Este nombre se utilizará en los catálogos de registro y reportes.
        </p>
    </div>

    <div class="pt-6 border-t border-slate-100">
        <button type="submit" class="btn-primary w-full py-3">
            <i class="fa-solid fa-check"></i>
            REGISTRAR RED
        </button>
    </div>
</form>