<!-- resources/views/padres/show.blade.php -->
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Detalles del Padre') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <ul role="list" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Tarjeta del Padre -->
                <li class="col-span-1 flex flex-col divide-y divide-gray-200 rounded-lg bg-white dark:bg-gray-800 text-center shadow">
                    <div class="flex flex-1 flex-col p-8">
                        <!-- Imagen del Padre -->
                        @if ($padre->foto_padre)
                            <img class="mx-auto h-32 w-32 flex-shrink-0 rounded-full" src="{{ asset('storage/' . $padre->foto_padre) }}" alt="Foto del Padre">
                        @else
                            <img class="mx-auto h-32 w-32 flex-shrink-0 rounded-full" src="https://via.placeholder.com/256" alt="No disponible">
                        @endif
                        <!-- Información del Padre -->
                        <h3 class="mt-6 text-sm font-medium text-gray-900 dark:text-gray-200">{{ $padre->nombre }}</h3>
                        <dl class="mt-1 flex flex-grow flex-col justify-between">
                            <dt class="sr-only">Red</dt>
                            <dd class="text-sm text-gray-500 dark:text-gray-400">Red: {{ $padre->red ?? 'N/A' }}</dd>
                            <dt class="sr-only">Teléfono</dt>
                            <dd class="text-sm text-gray-500 dark:text-gray-400">Teléfono: {{ $padre->telefono }}</dd>
                        </dl>
                    </div>
                </li>

                <!-- Tarjeta de los Hijos -->
                <li class="col-span-1 flex flex-col divide-y divide-gray-200 rounded-lg bg-white dark:bg-gray-800 shadow">
                    <div class="p-8">
                        <h4 class="text-lg font-semibold dark:text-gray-200">Hijos:</h4>
                        <ul role="list" class="mt-4 space-y-4">
                            @foreach ($padre->hijos as $hijo)
                                <li class="flex items-center justify-between p-4 bg-gray-100 dark:bg-gray-700 rounded-lg shadow-sm">
                                    <div>
                                        <h5 class="text-md font-medium text-gray-900 dark:text-gray-200">{{ $hijo->nombre }}</h5>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Edad: {{ $hijo->edad }} años</p>
                                    </div>
                                </li>
                            @endforeach
                            @if ($padre->hijos->isEmpty())
                                <p class="text-gray-600 dark:text-gray-400">No hay hijos registrados.</p>
                            @endif
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</x-app-layout>
