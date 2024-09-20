<!-- resources/views/padres/create.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Registrar Padre e Hijos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                <form action="{{ route('padres.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <!-- Información del Padre -->
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-2 dark:text-gray-200">Información del Padre</h3>
                        <div class="mb-4">
                            <label for="nombre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Padre</label>
                            <input type="text" name="nombre" id="nombre" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>

                        <div class="mb-4">
                            <label for="red" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Red</label>
                            <input type="text" name="red" id="red" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div class="mb-4">
                            <label for="telefono" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Teléfono</label>
                            <input type="text" name="telefono" id="telefono" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div class="mb-4">
                            <label for="foto_padre" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Foto del Padre</label>
                            <input type="file" id="file-input" name="foto_padre" accept="image/*" class="block mt-1">
                            <img id="photo" class="w-64 h-48 border border-gray-300 rounded-md hidden">
                        </div>
                    </div>

                    <!-- Información de los Hijos -->
                    <div class="mb-6">
                        <h3 class="text-xl font-semibold mb-2 dark:text-gray-200">Información de los Hijos</h3>
                        <div id="hijos-container">
                            <!-- Campos para los hijos se agregarán dinámicamente aquí -->
                        </div>
                        <button type="button" id="add-child" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Agregar Hijo
                        </button>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Registrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('file-input');
            const photo = document.getElementById('photo');

            fileInput.addEventListener('change', (event) => {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        photo.src = e.target.result;
                        photo.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Handle adding new children
            const addButton = document.getElementById('add-child');
            const hijosContainer = document.getElementById('hijos-container');
            let childIndex = 0;

            addButton.addEventListener('click', function () {
                childIndex++;
                const childFields = `
                    <div class="mb-4">
                        <label for="hijos[${childIndex}][nombre]" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Hijo ${childIndex}</label>
                        <input type="text" name="hijos[${childIndex}][nombre]" id="hijos[${childIndex}][nombre]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                    <div class="mb-4">
                        <label for="hijos[${childIndex}][edad]" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Edad del Hijo ${childIndex}</label>
                        <input type="number" name="hijos[${childIndex}][edad]" id="hijos[${childIndex}][edad]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    </div>
                `;
                hijosContainer.insertAdjacentHTML('beforeend', childFields);
            });
        });
    </script>
</x-app-layout>
