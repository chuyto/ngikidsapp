<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-users text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Editar Red') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                    Configuración · Actualizar Clasificación
                </p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card overflow-hidden">

            {{-- Header Superior --}}
            <div class="p-8 border-b border-slate-100 flex items-center">
                <div class="p-3 bg-brand-50 rounded-2xl border border-brand-100 text-brand-500">
                    <i class="fa-solid fa-pen text-2xl"></i>
                </div>
                <div class="ml-5">
                    <h3 class="font-heading text-xl text-slate-900">Modificar Detalles</h3>
                    <p class="text-sm font-semibold text-slate-500">Cambie el nombre o descripción de la red seleccionada.</p>
                </div>
            </div>

            <form action="{{ route('redes.update', $rede->id) }}" method="POST" class="p-8 space-y-8">
                @csrf
                @method('PUT')

                <div>
                    <label for="descripcion" class="label">Nombre de la Red</label>
                    <input id="descripcion" type="text" name="descripcion"
                           class="input"
                           placeholder="Nombre de la red"
                           value="{{ old('descripcion', $rede->descripcion) }}" required autofocus>
                    <p class="mt-2 text-xs font-semibold text-slate-400 italic">
                        Este cambio se reflejará inmediatamente en todos los padres asociados.
                    </p>
                </div>

                @error('descripcion')
                    <p class="text-sm font-bold text-brand-600">{{ $message }}</p>
                @enderror

                {{-- Footer de Acciones --}}
                <div class="flex items-center justify-between pt-8 border-t border-slate-100">
                    <a href="{{ route('redes.index') }}" class="text-xs font-black text-slate-400 hover:text-slate-600 transition uppercase tracking-widest">
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