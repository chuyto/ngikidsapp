<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Lista de Hijos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <a href="{{ route('hijos.create') }}" class="mb-4 inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Crear Hijo
                </a>

                <table class="min-w-full bg-white border-collapse">
                    <thead>
                        <tr>
                            <th class="border px-6 py-4 text-left">ID</th>
                            <th class="border px-6 py-4 text-left">Nombre</th>
                            <th class="border px-6 py-4 text-left">Padre</th>
                            <th class="border px-6 py-4 text-left">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hijos as $hijo)
                        <tr>
                            <td class="border px-6 py-4">{{ $hijo->id }}</td>
                            <td class="border px-6 py-4">{{ $hijo->nombre }}</td>
                            <td class="border px-6 py-4">{{ $hijo->padre->nombre }}</td>
                            <td class="border px-6 py-4">
                                <a href="{{ route('hijos.edit', $hijo->id) }}" class="text-blue-500 hover:text-blue-700">Editar</a>
                                <form action="{{ route('hijos.destroy', $hijo->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 ml-4">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
