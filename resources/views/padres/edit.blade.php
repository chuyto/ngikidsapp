<!-- resources/views/padres/edit.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Padre e Hijos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg p-6">
                <form action="{{ route('padres.update', $padre->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <!-- Información del Padre -->
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-2 dark:text-gray-200">Información del Padre</h3>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Padre</label>
                            <input type="text" name="nombre" value="{{ old('nombre', $padre->nombre) }}" class="mt-1 block w-full dark:bg-gray-700 dark:text-gray-300 border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Red</label>
                            <input type="text" name="red" value="{{ old('red', $padre->red) }}" class="mt-1 block w-full dark:bg-gray-700 dark:text-gray-300 border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                            <input type="text" name="telefono" value="{{ old('telefono', $padre->telefono) }}" class="mt-1 block w-full dark:bg-gray-700 dark:text-gray-300 border-gray-300 rounded-md shadow-sm">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Foto del Padre</label>
                            <input type="file" name="foto_padre" class="mt-1 block w-full dark:bg-gray-700 dark:text-gray-300 border-gray-300 rounded-md shadow-sm">
                            @if ($padre->foto_padre)
                                <img src="{{ Storage::url($padre->foto_padre) }}" alt="Foto del Padre" class="mt-4 max-w-xs">
                            @endif
                        </div>
                    </div>

                    <!-- Información de los Hijos -->
                    <div id="hijos-container" class="mb-6">
                        <h3 class="text-xl font-semibold mb-2 dark:text-gray-200">Información de los Hijos</h3>
                        @foreach ($padre->hijos as $index => $hijo)
                            <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4 hijo-row">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Hijo {{ $index + 1 }}</label>
                                    <input type="text" name="hijos[{{ $index }}][nombre]" value="{{ old('hijos.' . $index . '.nombre', $hijo->nombre) }}" class="mt-1 block w-full dark:bg-gray-700 dark:text-gray-300 border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Edad del Hijo {{ $index + 1 }}</label>
                                    <input type="number" name="hijos[{{ $index }}][edad]" value="{{ old('hijos.' . $index . '.edad', $hijo->edad) }}" class="mt-1 block w-full dark:bg-gray-700 dark:text-gray-300 border-gray-300 rounded-md shadow-sm">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" id="add-child" class="mb-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow-sm">
                        Agregar Hijo
                    </button>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow-sm">
                            Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let hijoIndex = {{ count($padre->hijos) }};
        document.getElementById('add-child').addEventListener('click', function () {
            const container = document.getElementById('hijos-container');
            const childFields = `
                <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4 hijo-row">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Hijo</label>
                        <input type="text" name="hijos[${hijoIndex}][nombre]" class="mt-1 block w-full dark:bg-gray-700 dark:text-gray-300 border-gray-300 rounded-md shadow-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Edad del Hijo</label>
                        <input type="number" name="hijos[${hijoIndex}][edad]" class="mt-1 block w-full dark:bg-gray-700 dark:text-gray-300 border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', childFields);
            hijoIndex++;
        });
    </script>
</x-app-layout>
