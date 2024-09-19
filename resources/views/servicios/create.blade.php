<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Registrar Servicio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                <form action="{{ route('servicios.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="descripcion_servicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción del Servicio</label>
                        <input type="text" name="descripcion_servicio" id="descripcion_servicio" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                    </div>

                    <div class="mb-4">
                        <label for="horario_servicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Horario del Servicio</label>
                        <div class="relative mt-1">
                            <input id="horario_servicio" type="text" name="horario_servicio" class="w-full rounded-md border-gray-300 bg-white py-1.5 pl-3 pr-12 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm" readonly role="combobox" aria-controls="options" aria-expanded="false" placeholder="Escoge una opción">
                            <button type="button" class="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-none" aria-label="Open options">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <ul class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm hidden" id="options" role="listbox">
                                <li class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900" id="option-0" role="option" tabindex="-1" data-value="8:00 AM">
                                    <span class="block truncate">8:00 AM</span>
                                </li>
                                <li class="relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900" id="option-1" role="option" tabindex="-1" data-value="11:30 AM">
                                    <span class="block truncate">11:30 AM</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="fecha_servicio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Fecha del Servicio</label>
                        <input type="date" name="fecha_servicio" id="fecha_servicio" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                    </div>

                    <div class="mb-4">
                        <label for="activo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Activo</label>
                        <select name="activo" id="activo" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </select>
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
            const combobox = document.getElementById('horario_servicio');
            const optionsMenu = document.getElementById('options');
            const optionItems = optionsMenu.querySelectorAll('li');
            let isOpen = false;

            combobox.addEventListener('click', function () {
                optionsMenu.classList.toggle('hidden');
                isOpen = !isOpen;
                combobox.setAttribute('aria-expanded', isOpen);
            });

            optionItems.forEach(item => {
                item.addEventListener('click', function () {
                    combobox.value = item.getAttribute('data-value');
                    optionsMenu.classList.add('hidden');
                    isOpen = false;
                    combobox.setAttribute('aria-expanded', isOpen);
                });
            });

            document.addEventListener('click', function (event) {
                if (!combobox.contains(event.target) && !optionsMenu.contains(event.target)) {
                    optionsMenu.classList.add('hidden');
                    isOpen = false;
                    combobox.setAttribute('aria-expanded', isOpen);
                }
            });
        });
    </script>
</x-app-layout>
