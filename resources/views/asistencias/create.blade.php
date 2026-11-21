<x-app-layout>
    {{-- Header del Componente --}}
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-clipboard-check text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Control de Asistencia') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                    Operaciones · NGI Kids
                </p>
            </div>
        </div>
    </x-slot>

    <div class="space-y-10">

        @if($servicioActivo)
            {{-- SECCIÓN 1: Buscador --}}
            <div class="max-w-2xl mx-auto text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 border border-emerald-200 px-4 py-1.5 text-xs font-extrabold text-emerald-700 uppercase tracking-widest">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Servicio activo: {{ $servicioActivo->descripcion_servicio }}
                </span>

                <ol class="mt-5 flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-wider text-slate-400" aria-label="Pasos del registro">
                    <li class="text-brand-600">1. Buscar folio</li><li aria-hidden="true">→</li>
                    <li>2. Confirmar niños</li><li aria-hidden="true">→</li>
                    <li>3. Asignar fichas</li><li aria-hidden="true">→</li>
                    <li>4. Firmar</li>
                </ol>

                <div class="relative mt-6 card p-3 flex items-center gap-2 flex-wrap">
                    <i class="fa-solid fa-magnifying-glass text-xl text-slate-400 ml-4"></i>
                    <label for="uuid_short" class="sr-only">Folio de credencial</label>
                    <input type="text" id="uuid_short" maxlength="6" minlength="6" inputmode="text"
                        class="flex-1 min-w-[140px] bg-transparent border-none focus:ring-0 focus:outline-none text-2xl font-extrabold uppercase tracking-[0.2em] placeholder-slate-300"
                        placeholder="CREDENCIAL" autofocus autocomplete="off">
                    <button type="button" id="scanButton" title="Escanear credencial con la cámara"
                        class="btn-secondary px-5 inline-flex items-center gap-2">
                        <i class="fa-solid fa-qrcode"></i>
                        <span class="hidden sm:inline">ESCANEAR</span>
                    </button>
                    <button id="searchButton"
                        class="btn-primary px-8">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        BUSCAR
                    </button>
                </div>
            </div>

            {{-- SECCIÓN 2: Contenedor de Resultados AJAX --}}
            <div id="resultContainer" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch min-h-[450px] px-1 lg:px-4">
                {{-- Aquí se inyecta la ficha mediante JS --}}
            </div>

        @else
            {{-- ESTADO DE BLOQUEO --}}
            <div class="max-w-xl mx-auto pt-6 pb-16 text-center">
                <div class="card inline-flex flex-col items-center px-12 py-10">
                    <div class="w-16 h-16 rounded-full bg-brand-100 text-brand-600 flex items-center justify-center text-2xl mb-5">
                        <i class="fa-solid fa-circle-exclamation"></i>
                    </div>
                    <h3 class="font-heading text-2xl text-slate-900">Acceso Restringido</h3>
                    <p class="text-sm font-semibold text-slate-500 mt-2 leading-relaxed">
                        No se ha detectado ningún <span class="text-brand-600">servicio activo</span>.
                        Activa un servicio para comenzar el registro de asistencia.
                    </p>
                    @can('servicios.ver')
                    <a href="{{ route('servicios.index') }}" class="btn-primary mt-8">
                        <i class="fa-solid fa-calendar-days"></i> Ir a Servicios
                    </a>
                    @endcan
                </div>
            </div>
        @endif
    </div>

    {{-- MODAL ESCÁNER: lee el QR y pone el UID en el buscador (no abre links) --}}
    <div id="scannerModal" class="hidden fixed inset-0 z-50 flex items-end sm:items-center justify-center" role="dialog" aria-modal="true" aria-label="Escanear credencial">
        <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm"></div>
        <div class="relative w-full sm:max-w-md bg-white rounded-t-[2rem] sm:rounded-[2rem] p-6 pb-8 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-heading text-lg text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-qrcode text-brand-500"></i> Escanear credencial
                </h3>
                <button type="button" id="scannerClose" aria-label="Cerrar escáner"
                    class="w-10 h-10 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="relative rounded-2xl overflow-hidden bg-black aspect-square">
                <div id="scannerViewport" class="w-full h-full"></div>
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true">
                    <div class="relative w-56 h-56">
                        <span class="absolute top-0 left-0 w-10 h-10 border-t-4 border-l-4 border-brand-500 rounded-tl-xl"></span>
                        <span class="absolute top-0 right-0 w-10 h-10 border-t-4 border-r-4 border-brand-500 rounded-tr-xl"></span>
                        <span class="absolute bottom-0 left-0 w-10 h-10 border-b-4 border-l-4 border-brand-500 rounded-bl-xl"></span>
                        <span class="absolute bottom-0 right-0 w-10 h-10 border-b-4 border-r-4 border-brand-500 rounded-br-xl"></span>
                    </div>
                </div>
            </div>
            <p id="scannerStatus" class="text-center text-sm font-semibold text-slate-500 mt-4 min-h-[1.5rem]">Apunta la cámara al QR de la credencial…</p>
            <div class="flex gap-3 mt-4">
                <button type="button" id="scannerTorch"
                    class="hidden btn-secondary flex-1 py-3 items-center justify-center gap-2">
                    <i class="fa-solid fa-lightbulb"></i> LINTERNA
                </button>
                <button type="button" id="scannerCancel"
                    class="btn-primary flex-1 py-3">
                    CANCELAR
                </button>
            </div>
        </div>
    </div>

    {{-- SCRIPTS --}}
    @vite('resources/js/scanner-asistencia.js')
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchBtn = document.getElementById('searchButton');
            const inputUuid = document.getElementById('uuid_short');
            let signaturePad = null;

            // Los datos vienen de la base de datos y se inyectan en HTML:
            // se escapan para evitar XSS y atributos/plantillas rotas.
            const escapeHtml = (valor) => String(valor ?? '').replace(/[&<>"']/g, (caracter) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            })[caracter]);

            const safeUrl = (valor) => {
                if (!valor) return '';
                try {
                    const url = new URL(valor, window.location.origin);
                    return ['http:', 'https:'].includes(url.protocol) ? escapeHtml(url.href) : '';
                } catch {
                    return '';
                }
            };

            if (inputUuid) {
                inputUuid.addEventListener('keypress', (e) => { if (e.key === 'Enter') searchBtn.click(); });
                inputUuid.addEventListener('input', () => {
                    inputUuid.value = inputUuid.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
                    if (inputUuid.value.length === 6) searchBtn.click();
                });

                // Si llegamos con ?codigo=XXXXXX (QR de la tarjeta digital),
                // cargamos el código y buscamos automáticamente al padre.
                const params = new URLSearchParams(window.location.search);
                const codigo = params.get('codigo');
                if (codigo) {
                    inputUuid.value = codigo.toUpperCase().trim();
                    searchBtn.click();
                }
            }

            $(document).on('click', '#searchButton', function () {
                const uuid = $('#uuid_short').val();
                if(uuid.length !== 6) {
                    window.showAlert('La credencial debe tener 6 caracteres.', true);
                    return;
                }

                searchBtn.disabled = true;
                searchBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> BUSCANDO';

                $.ajax({
                    url: '{{ route("asistencias.search") }}',
                    method: 'GET',
                    data: { uuid_short: uuid },
                    success: function(response) {
                        if (response.padre) {
                            renderResult(response.padre);
                        } else {
                            window.showAlert('Credencial no encontrada.', true);
                            inputUuid.select();
                        }
                    },
                    error: () => window.showAlert('No fue posible realizar la búsqueda.', true),
                    complete: () => {
                        searchBtn.disabled = false;
                        searchBtn.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> BUSCAR';
                    },
                });
            });

            function renderResult(padre) {
                const foto = safeUrl(padre.foto_padre);
                const avatar = foto
                    ? `<img class="h-36 w-36 rounded-3xl object-cover border-4 border-white shadow-lg mx-auto bg-slate-100" src="${foto}" alt="Foto del responsable">`
                    : `<div class="h-36 w-36 rounded-3xl bg-brand-100 text-brand-600 border-4 border-white shadow-lg mx-auto flex items-center justify-center text-5xl font-extrabold" aria-hidden="true">${escapeHtml((padre.nombre || '?').charAt(0))}</div>`;
                const esSalida = padre.hora_entrada && !padre.hora_salida;
                const tituloFirma = esSalida ? "FIRMA DE SALIDA (ENTREGA)" : "FIRMA DE ENTRADA (INGRESO)";

                let html = `
                    <div class="lg:col-span-4 flex flex-col h-full">
                        <div class="card p-8 h-full flex flex-col justify-between text-center">
                            <div>
                                <div class="relative inline-block mb-5">
                                    ${avatar}
                                    <span class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 bg-slate-800 text-white text-[10px] font-extrabold px-4 py-1 rounded-full uppercase tracking-widest shadow-md">
                                        ${escapeHtml(padre.uuid_short)}
                                    </span>
                                </div>
                                <h3 class="font-heading text-2xl text-slate-900 leading-tight">${escapeHtml(padre.nombre)}</h3>
                                <p class="badge-info mt-3">
                                    <i class="fa-solid fa-circle-nodes"></i> RED: ${escapeHtml(padre.red ? padre.red.descripcion : 'GENERAL')}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-8 flex flex-col h-full mt-8 lg:mt-0">
                        <div class="card p-8 h-full">
                            <form id="asistenciaForm" class="h-full flex flex-col justify-between">
                                @csrf
                                <input type="hidden" name="uuid_short" value="${escapeHtml(padre.uuid_short)}">
                                <input type="hidden" name="signature" id="signatureInput">

                                <div>
                                    <h4 class="text-[11px] font-extrabold text-slate-400 uppercase tracking-[0.3em] mb-8 flex items-center">
                                        <i class="fa-solid fa-children mr-3 text-brand-500"></i> Control de Hijos
                                    </h4>

                                    <div class="grid grid-cols-1 gap-4 mb-8">
                                        ${padre.hijos.map((hijo) => {
                                            const asistencia = padre.registros && padre.registros[hijo.id] ? padre.registros[hijo.id] : null;
                                            const yaTieneRegistro = asistencia !== null;
                                            const estaAsistiendo = yaTieneRegistro && asistencia.estado === 'asistio';

                                            return `
                                                <div class="p-5 rounded-2xl border transition-all duration-200"
                                                     :class="asistiendo ? 'bg-emerald-50/60 border-emerald-200 shadow-sm' : 'bg-slate-50 border-slate-200'"
                                                     x-data="{ asistiendo: ${estaAsistiendo} }">
                                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                                        <div class="flex items-center gap-3">
                                                            <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-brand-500 shadow-sm">
                                                                <i class="fa-solid fa-baby"></i>
                                                            </div>
                                                            <div>
                                                                <span class="font-extrabold text-slate-800">${escapeHtml(hijo.nombre)}</span>
                                                                <p class="text-xs font-semibold text-slate-400">${escapeHtml(hijo.edad)} años</p>
                                                            </div>
                                                        </div>
                                                        <button type="button" @click="${yaTieneRegistro ? '' : 'asistiendo = !asistiendo'}"
                                                            :class="asistiendo ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-slate-500 border-slate-300'"
                                                            class="inline-flex min-w-[140px] items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-xs font-extrabold uppercase tracking-wide transition-all duration-200 focus:outline-none focus-visible:ring-4 focus-visible:ring-emerald-100 ${yaTieneRegistro ? 'cursor-not-allowed opacity-80' : 'cursor-pointer hover:border-emerald-400'}"
                                                            ${yaTieneRegistro ? 'disabled' : ''}
                                                            :aria-pressed="asistiendo"
                                                            aria-label="Cambiar asistencia de ${escapeHtml(hijo.nombre)}">
                                                            <input type="hidden" class="hijo-status-input" data-nombre="${escapeHtml(hijo.nombre)}" name="hijos[${hijo.id}][status]" :value="asistiendo ? 'asistio' : 'no_asistio'">
                                                            <i class="fa-solid" :class="asistiendo ? 'fa-circle-check' : 'fa-circle-xmark'"></i>
                                                            <span x-text="asistiendo ? 'Sí asiste' : 'No asiste'"></span>
                                                        </button>
                                                    </div>
                                                    <div x-show="asistiendo" x-cloak x-transition.duration.200ms class="mt-4 pt-4 border-t border-slate-200">
                                                        <label class="text-xs font-bold text-slate-500 mb-2 block">
                                                            <i class="fa-solid fa-hashtag mr-1 text-brand-500"></i>Número de ficha
                                                        </label>
                                                        <input type="text" name="hijos[${hijo.id}][ficha]"
                                                            value="${escapeHtml(asistencia ? (asistencia.ficha || '') : '')}"
                                                            class="input max-w-[140px] py-2.5 ${yaTieneRegistro ? 'cursor-not-allowed' : ''}"
                                                            placeholder="000" :required="asistiendo" ${yaTieneRegistro ? 'readonly' : ''}>
                                                    </div>
                                                </div>
                                            `;
                                        }).join('')}
                                    </div>

                                    ${!padre.hora_salida ? `
                                        <div class="mt-4">
                                            <div class="flex justify-between items-center mb-4">
                                                <label class="text-xs font-extrabold text-slate-400 uppercase tracking-widest">${tituloFirma}</label>
                                                <button type="button" id="clearSignature" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 px-4 py-1.5 rounded-full transition-colors cursor-pointer">
                                                    <i class="fa-solid fa-eraser"></i> Limpiar
                                                </button>
                                            </div>
                                            <div class="relative bg-slate-50 rounded-[1.5rem] border-2 border-dashed border-slate-200 overflow-hidden">
                                                <canvas id="signature-pad" class="w-full h-48 touch-none cursor-crosshair"></canvas>
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>

                                <div class="mt-10">
                                    ${!padre.hora_salida ? `
                                        <button type="submit" class="btn-primary w-full py-4 text-sm">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            ${padre.hora_entrada ? 'REGISTRAR SALIDA / ENTREGA' : 'CONFIRMAR INGRESO'}
                                        </button>
                                    ` : `
                                        <div class="w-full py-5 bg-slate-100 text-slate-400 font-extrabold rounded-full text-center border-2 border-dashed border-slate-200">
                                            <i class="fa-solid fa-circle-check mr-2 text-emerald-500"></i> SERVICIO COMPLETADO
                                        </div>
                                    `}
                                </div>
                            </form>
                        </div>
                    </div>
                `;
                $('#resultContainer').hide().html(html).fadeIn(600);

                if (!padre.hora_salida) {
                    initSignaturePad();
                }
            }

            function initSignaturePad() {
                const canvas = document.getElementById('signature-pad');
                if(!canvas) return;
                signaturePad = new SignaturePad(canvas, {
                    backgroundColor: 'rgba(248,250,252,0)',
                    penColor: 'rgb(225, 29, 72)'
                });

                const resizeCanvas = () => {
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    canvas.getContext("2d").scale(ratio, ratio);
                    signaturePad.clear();
                };

                window.addEventListener("resize", resizeCanvas);
                resizeCanvas();

                document.getElementById('clearSignature').addEventListener('click', () => signaturePad.clear());
            }

            $(document).on('submit', '#asistenciaForm', function(e) {
                e.preventDefault();
                const form = this;
                const submitButton = form.querySelector('button[type="submit"]');

                let algunoAsiste = false;
                $('.hijo-status-input').each(function() {
                    if ($(this).val() === 'asistio') algunoAsiste = true;
                });

                if (!algunoAsiste) {
                    window.showAlert('Debe marcar al menos a un niño para registrar', true);
                    return;
                }

                if (signaturePad && signaturePad.isEmpty()) {
                    window.showAlert('La firma es obligatoria para continuar', true);
                    return;
                }

                if (signaturePad) {
                    $('#signatureInput').val(signaturePad.toDataURL());
                }

                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> GUARDANDO FIRMA...';

                $.ajax({
                    url: '{{ route("asistencias.store") }}',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        window.showAlert(response.success);
                        document.getElementById('resultContainer').innerHTML = '';
                        inputUuid.value = '';
                        inputUuid.focus();
                    },
                    error: function(xhr) {
                        window.showAlert(xhr.responseJSON?.error || 'Error al procesar. Puedes volver a intentarlo.', true);
                    },
                    complete: function() {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> VOLVER A INTENTAR';
                    },
                });
            });
        });
    </script>
</x-app-layout>
