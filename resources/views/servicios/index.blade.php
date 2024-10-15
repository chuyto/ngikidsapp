<!-- resources/views/padres/index.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Servicios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg p-6">
                <div class="flex justify-between mb-6">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-200">Servicios</h1>
                    <a href="{{ route('servicios.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow-sm hover:bg-indigo-700">
                        Agregar Nuevo Registro
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Descripción del Servicio</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Horario del Servicio</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Fecha del Servicio</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">Activo</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 text-left text-sm font-semibold text-gray-900 dark:text-gray-200">
                                    <span class="sr-only">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($servicios as $servicio)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 dark:text-gray-200">{{ $servicio->descripcion_servicio }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $servicio->horario_servicio }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $servicio->fecha_servicio }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 dark:text-gray-300">{{ $servicio->activo ? 'Si' : 'No' }}</td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium">
                                        <a href="{{ route('servicios.show', $servicio->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Ver Servicio</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
