<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Registrar Asistencia') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg p-6">
                <!-- Formulario para ingresar UUID y buscar datos -->
                <div class="mb-4">
                    <label for="uuid_short" class="block text-sm font-medium text-gray-700 dark:text-gray-300">UUID del Padre</label>
                    <input type="text" id="uuid_short" name="uuid_short" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    <button id="searchButton" class="bg-blue-500 text-white px-4 py-2 rounded-md mt-2">Buscar</button>
                </div>

                <div id="resultContainer"></div>

                <!-- Formulario para registrar la asistencia -->
                <form id="asistenciaForm" class="mt-6" style="display: none;" onsubmit="return false;">
                    @csrf
                    <input type="hidden" id="uuid_short_input" name="uuid_short">

                    <div class="mb-4" id="fichaContainer">
                        <label for="numero_ficha" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Número de Ficha</label>
                        <input type="text" id="numero_ficha" name="numero_ficha" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                    </div>

                    <div id="hijosContainer" class="mb-4">
                        <!-- Aquí se agregarán los campos dinámicos para cada hijo -->
                    </div>

                    <button type="submit" id="submitButton" class="bg-green-500 text-white px-4 py-2 rounded-md">Registrar Asistencia</button>
                </form>

                <!-- Alert Notification -->
                <div id="alertNotification" class="hidden bg-yellow-50 p-4 rounded-md mt-4 relative">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Atención necesaria</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p id="alertMessage">El niño ya ha sido entregado.</p>
                            </div>
                        </div>
                    </div>
                    <button id="closeAlert" class="absolute top-2 right-2 text-yellow-400 hover:text-yellow-600">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M6.293 4.293a1 1 0 011.414 0L10 5.586l2.293-1.293a1 1 0 111.414 1.414L11.414 7l2.293 2.293a1 1 0 01-1.414 1.414L10 8.414l-2.293 2.293a1 1 0 01-1.414-1.414L8.586 7 6.293 4.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Global notification live region -->
    <div id="notification" aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-start sm:p-6 hidden">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <div class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p id="notification-message" class="text-sm font-medium text-gray-900">Successfully saved!</p>
                            <p class="mt-1 text-sm text-gray-500">Anyone with a link can now view this file.</p>
                        </div>
                        <div class="ml-4 flex flex-shrink-0">
                            <button type="button" id="close-notification" class="inline-flex rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <span class="sr-only">Close</span>
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Mostrar notificación si hay un mensaje de éxito
            document.getElementById('asistenciaForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Evita el envío por defecto

    // Obtiene los datos del formulario
    const formData = $(this).serialize(); // Usa jQuery para serializar el formulario

    // Envía los datos al servidor
    $.ajax({
        url: '{{ route("asistencias.store") }}', // Ruta de tu método store
        method: 'POST',
        data: formData,
        success: function(response) {
            $('#alertNotification').removeClass('hidden'); // Mostrar notificación
            $('#alertMessage').text(response.success); // Mensaje de éxito
            // Deshabilitar el formulario para evitar modificaciones
            $('#asistenciaForm input, #asistenciaForm select').prop('disabled', true);
        },
        error: function(xhr) {
            // Manejar errores, puedes mostrar un mensaje al usuario
            $('#alertNotification').removeClass('hidden'); // Mostrar notificación
            $('#alertMessage').text('Error al registrar asistencia.'); // Mensaje de error
        }
    });
});


            // Manejar búsqueda por UUID
           // Manejar búsqueda por UUID
// Manejar búsqueda por UUID
document.getElementById('searchButton').addEventListener('click', function () {
    const uuid = document.getElementById('uuid_short').value;
    $.ajax({
        url: '{{ route("asistencias.search") }}',
        method: 'GET',
        data: { uuid_short: uuid },
        success: function(response) {
            // Reiniciar el formulario de asistencia al realizar una nueva búsqueda
            $('#hijosContainer select').prop('disabled', false); // Habilitar todos los campos de asistencia
            $('#numero_ficha').removeAttr('required'); // Quitar el atributo required
            $('#fichaContainer').show(); // Mostrar el campo de ficha
            $('#asistenciaForm button[type="submit"]').show(); // Mostrar el botón de registro

            if (response.padre) {
                const padre = response.padre;
                $('#uuid_short_input').val(padre.uuid_short);

                // Generar HTML para la asistencia de los hijos
                let hijosHtml = '';
                padre.hijos.forEach((hijo, index) => {
                    const asistenciaHijo = padre.hijos_asistencia[hijo.nombre] || '';
                    hijosHtml += `
                        <div class="mb-4">
                            <label for="hijo_${index}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">${hijo.nombre} (${hijo.edad} años)</label>
                            <select id="hijo_${index}" name="hijos_asistencia[${hijo.nombre}]" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                <option value="asistio" ${asistenciaHijo === 'asistio' ? 'selected' : ''}>Asistió</option>
                                <option value="no_asistio" ${asistenciaHijo === 'no_asistio' ? 'selected' : ''}>No asistió</option>
                            </select>
                        </div>
                    `;
                });

                // Agregar las horas de entrada y salida al HTML
                const resultHtml = `
                    <div class="flex flex-col md:flex-row items-start">
                        <div class="md:w-1/2 md:pr-6 mb-6 md:mb-0">
                            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                                <img class="mx-auto h-32 w-32 flex-shrink-0 mt-4" src="{{ asset('storage') }}/${padre.foto_padre}" alt="Foto del Padre">
                                <h3 class="text-xl font-semibold dark:text-gray-200">${padre.nombre}</h3>
                                <p class="text-gray-600 dark:text-gray-400">Red: ${padre.red || 'N/A'}</p>
                                <p class="text-gray-600 dark:text-gray-400">Teléfono: ${padre.telefono}</p>
                                <p class="text-gray-600 dark:text-gray-400">Hora de Entrada: ${padre.hora_entrada || 'No registrado'}</p>
                                <p class="text-gray-600 dark:text-gray-400">Hora de Entrega: ${padre.hora_salida || 'No registrado'}</p>
                            </div>
                        </div>
                        <div class="md:w-1/2">
                            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
                                <h4 class="text-lg font-semibold dark:text-gray-200 mt-4">Hijos:</h4>
                                <ul class="list-disc ml-5">
                                    ${padre.hijos.map(hijo => `<li class="text-gray-600 dark:text-gray-400">${hijo.nombre} (${hijo.edad} años)</li>`).join('')}
                                </ul>
                            </div>
                        </div>
                    </div>
                `;

                $('#resultContainer').html(resultHtml);
                $('#hijosContainer').html(hijosHtml);  // Añadir los campos generados al contenedor

                // Mostrar el formulario de asistencia
                $('#asistenciaForm').show();

                // Verificar si ya tiene entrada registrada
                if (padre.hora_entrada) {
                    $('#fichaContainer').hide(); // Ocultar campo de ficha
                    $('#numero_ficha').removeAttr('required'); // Quitar el atributo required

                    // Mostrar el alert en lugar del popup
                    if (padre.hora_salida) {
                        $('#alertNotification').removeClass('hidden');
                        $('#asistenciaForm button[type="submit"]').hide(); // Ocultar el botón de registro

                        // Bloquear campos de asistencia
                        $('#hijosContainer select').prop('disabled', true);

                        // Configurar el temporizador para ocultar el alert después de 5 segundos
                        setTimeout(function() {
                            $('#alertNotification').addClass('hidden');
                        }, 5000); // Ocultar después de 5 segundos
                    } else {
                        $('#alertNotification').addClass('hidden');
                        $('#asistenciaForm button[type="submit"]').show(); // Mostrar el botón de registro si no hay hora_salida
                    }
                } else {
                    $('#fichaContainer').show(); // Mostrar campo de ficha si no tiene entrada
                    $('#numero_ficha').attr('required', true); // Asegurarse de que el campo esté requerido
                    $('#asistenciaForm button[type="submit"]').show();
                }
            } else {
                $('#resultContainer').html('<p class="text-red-500">No se encontró un padre con ese UUID.</p>');
                $('#asistenciaForm').hide(); // Ocultar el formulario si no se encuentra el padre
            }
        }
    });
});



            // Cerrar la notificación
            document.getElementById('close-notification').addEventListener('click', function() {
                document.getElementById('notification').classList.add('hidden');
            });

            // Cerrar la alerta
            document.getElementById('closeAlert').addEventListener('click', function() {
                $('#alertNotification').addClass('hidden');
            });

            // Manejar el envío del formulario
            document.getElementById('asistenciaForm').addEventListener('submit', function () {
                // Aquí podrías hacer el envío del formulario si es necesario
                alert('Asistencia registrada.');

                // Bloquear todos los campos del formulario
                $('#asistenciaForm input, #asistenciaForm select').prop('disabled', true);
                $('#alertNotification').removeClass('hidden'); // Mostrar notificación
                $('#alertMessage').text('Asistencia registrada y bloqueada para modificaciones.'); // Mensaje de alerta
            });
        });
    </script>
</x-app-layout>
