<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    {{-- PWA / Favicon --}}
    <meta name="theme-color" content="#CF7001">
    <meta name="application-name" content="NGIKids">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="NGIKids">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('img/icons/apple-touch-icon.png') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito-sans:400,500,600,700,800&family=varela-round:400&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-cream font-sans h-app overflow-hidden">

    {{-- NOTIFICACIÓN GLOBAL --}}
    <div id="alertNotification" class="hidden fixed top-6 inset-x-0 z-[100] flex justify-center px-4 pointer-events-none" role="alert" aria-live="polite">
        <div id="alertBox" class="pointer-events-auto min-w-[300px] max-w-md bg-white text-slate-800 px-5 py-4 rounded-3xl shadow-[0_20px_50px_rgba(30,25,24,0.25)] flex items-center gap-4 border border-slate-200 transform transition-all duration-500 -translate-y-40">
            <div id="alertIconContainer" class="p-3 rounded-full bg-brand-100 text-brand-600 shrink-0">
                <i id="alertIcon" class="fa-solid fa-circle-info text-lg"></i>
            </div>
            <div class="min-w-0">
                <p id="alertTitle" class="text-[10px] font-extrabold uppercase tracking-[0.2em] text-slate-400">Sistema NGI Kids</p>
                <p id="alertMessage" class="font-bold text-sm tracking-tight"></p>
            </div>
            <button type="button" id="alertClose" class="ml-auto btn-icon w-8 h-8" aria-label="Cerrar notificación">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <dialog id="confirmDialog" class="m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl bg-white p-0 shadow-2xl backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm">
        <div class="p-7">
            <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-xl mb-5">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h2 class="font-heading text-xl text-slate-900">Confirmar acción</h2>
            <p id="confirmMessage" class="mt-2 text-sm font-semibold text-slate-500"></p>
            <div class="mt-7 flex justify-end gap-3">
                <button type="button" id="confirmCancel" class="btn-ghost">Cancelar</button>
                <button type="button" id="confirmAccept" class="btn-danger">Sí, eliminar</button>
            </div>
        </div>
    </dialog>

    <x-banner />

    <div x-data="{ sidebarOpen: false }"
         @keydown.escape="sidebarOpen = false"
         class="flex h-app overflow-hidden bg-cream relative">

        {{-- Fondo decorativo para el efecto vidrio --}}
        <div class="pointer-events-none fixed inset-0 z-0">
            <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-brand-200/70 blur-3xl"></div>
            <div class="absolute top-1/3 -right-32 h-[28rem] w-[28rem] rounded-full bg-amber-100/80 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/4 h-80 w-80 rounded-full bg-emerald-200/60 blur-3xl"></div>
            <div class="absolute top-1/2 left-1/2 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand-100/60 blur-3xl"></div>
        </div>

        @auth
            {{-- Overlay móvil --}}
            <div x-show="sidebarOpen"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="sidebarOpen = false"
                 class="fixed inset-0 z-20 bg-slate-900/30 backdrop-blur-sm lg:hidden">
            </div>

            {{-- SIDEBAR --}}
            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed top-0 left-0 z-30 w-72 flex flex-col h-app transition duration-300 transform bg-white/70 backdrop-blur-2xl backdrop-saturate-150 border-r border-white/60 lg:translate-x-0 lg:static lg:h-auto shadow-[4px_0_24px_rgba(30,25,24,0.06)]">

                <div class="flex items-center gap-3 px-6 h-20 border-b border-slate-100 flex-shrink-0">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <img class="h-10 w-auto" src="{{ asset('img/logo-ngikids.png') }}" alt="Logo NGI Kids">
                        <div>
                            <span class="font-heading text-lg text-slate-900 leading-none">NGIKids</span>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Ministerio Infantil</p>
                        </div>
                    </a>
                </div>

                <div class="flex flex-col flex-grow min-h-0">
                    @livewire('navigation-menu')
                </div>

                {{-- Botón de instalación PWA (se muestra cuando el navegador lo permite) --}}
                <div class="px-4 py-3 border-t border-slate-100 flex-shrink-0">
                    <button id="installPwaBtn" class="hidden btn-secondary w-full text-xs justify-center">
                        <i class="fa-solid fa-download mr-1"></i> Instalar App
                    </button>
                </div>
            </aside>
        @endauth

        <div class="flex flex-col flex-1 w-full min-w-0 min-h-0 relative z-10">
            {{-- Header móvil --}}
            <header class="flex items-center justify-between px-5 h-16 bg-white/70 backdrop-blur-2xl border-b border-white/60 lg:hidden sticky top-0 z-10">
                <button @click.stop="sidebarOpen = true" class="w-10 h-10 flex items-center justify-center rounded-full text-slate-500 hover:bg-white/80 transition-colors cursor-pointer" aria-label="Abrir menú">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="font-heading text-slate-900">NGIKids App</div>
                <div class="w-10"></div>
            </header>

            {{-- Header desktop --}}
            @if (isset($header))
                <header class="hidden lg:flex items-center h-20 bg-white/60 backdrop-blur-2xl backdrop-saturate-150 border-b border-white/60 sticky top-0 z-10 px-8">
                    {{ $header }}
                </header>
            @endif

            <main class="flex-1 overflow-y-auto min-h-0">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @stack('modals')
    @livewireScripts

    {{-- Script Global de Alertas --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        window.showAlert = function(msg, isError = false) {
            const box = $('#alertBox');
            const container = $('#alertNotification');

            $('#alertMessage').text(msg);

            if (isError) {
                box.removeClass('bg-white border-slate-200');
                $('#alertIconContainer').removeClass('bg-brand-100 text-brand-600').addClass('bg-red-100 text-red-600');
                $('#alertIcon').removeClass('fa-circle-info').addClass('fa-circle-exclamation');
                $('#alertTitle').text('Atención');
            } else {
                box.addClass('bg-white border-slate-200');
                $('#alertIconContainer').removeClass('bg-red-100 text-red-600').addClass('bg-emerald-100 text-emerald-600');
                $('#alertIcon').removeClass('fa-circle-exclamation').addClass('fa-circle-check');
                $('#alertTitle').text('Sistema NGI Kids');
            }

            container.removeClass('hidden');
            setTimeout(() => {
                box.removeClass('-translate-y-40').addClass('translate-y-0');
            }, 50);

            setTimeout(() => {
                box.removeClass('translate-y-0').addClass('-translate-y-40');
                setTimeout(() => container.addClass('hidden'), 500);
            }, 4000);
        }

        $('#alertClose').on('click', () => $('#alertNotification').addClass('hidden'));

        @if(session('success'))
            window.addEventListener('DOMContentLoaded', () => window.showAlert(@js(session('success'))));
        @elseif(session('error'))
            window.addEventListener('DOMContentLoaded', () => window.showAlert(@js(session('error')), true));
        @endif

        (() => {
            const dialog = document.getElementById('confirmDialog');
            const message = document.getElementById('confirmMessage');
            let pendingForm = null;

            document.addEventListener('submit', event => {
                const form = event.target.closest('form[data-confirm]');
                if (!form || form.dataset.confirmed === 'true') return;
                event.preventDefault();
                pendingForm = form;
                message.textContent = form.dataset.confirm;
                dialog.showModal();
            }, true);

            document.getElementById('confirmCancel').addEventListener('click', () => {
                pendingForm = null;
                dialog.close();
            });
            document.getElementById('confirmAccept').addEventListener('click', () => {
                if (!pendingForm) return;
                pendingForm.dataset.confirmed = 'true';
                dialog.close();
                pendingForm.requestSubmit();
            });
        })();

        // Filas dinámicas de hijos dentro del formulario de registro familiar.
        // Se usa delegación porque el formulario se teletransporta al abrir el modal.
        document.addEventListener('click', event => {
            const addButton = event.target.closest('[data-add-child]');
            if (addButton) {
                const form = addButton.closest('form');
                const container = form?.querySelector('[data-children-container]');
                if (!container) return;

                const index = Number(container.dataset.nextIndex || container.querySelectorAll('[data-child-row]').length);
                const number = container.querySelectorAll('[data-child-row]').length + 1;
                const maxDate = @js(now()->subDay()->toDateString());
                const row = document.createElement('div');
                row.className = 'p-6 bg-slate-50 rounded-2xl border border-slate-200 grid grid-cols-1 md:grid-cols-3 gap-6 relative';
                row.dataset.childRow = '';
                row.innerHTML = `
                    <div class="md:col-span-2">
                        <label class="label" for="child-name-${index}">Nombre del hijo ${number}</label>
                        <input id="child-name-${index}" type="text" name="hijos[${index}][nombre]" class="input" placeholder="Nombre completo" required>
                    </div>
                    <div>
                        <label class="label" for="child-birthdate-${index}">Fecha de nacimiento</label>
                        <input id="child-birthdate-${index}" type="date" name="hijos[${index}][fecha_nacimiento]" max="${maxDate}" class="input" required>
                    </div>
                    <button type="button" data-remove-child aria-label="Quitar hijo"
                            class="absolute top-4 right-4 w-9 h-9 flex items-center justify-center rounded-full bg-white border border-slate-200 text-brand-500 shadow-sm hover:bg-brand-50 cursor-pointer transition-colors">
                        <i class="fa-solid fa-xmark"></i>
                    </button>`;
                container.appendChild(row);
                container.dataset.nextIndex = String(index + 1);
                row.querySelector('input')?.focus();
                return;
            }

            const removeButton = event.target.closest('[data-remove-child]');
            if (removeButton) removeButton.closest('[data-child-row]')?.remove();
        });
    </script>

    {{-- PWA: registro del Service Worker y botón de instalación --}}
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('{{ asset("sw.js") }}').catch(() => {});
        }

        (function () {
            let deferredPrompt = null;
            const btn = document.getElementById('installPwaBtn');

            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                if (btn) btn.classList.remove('hidden');
            });

            if (btn) {
                btn.addEventListener('click', async () => {
                    if (!deferredPrompt) return;
                    deferredPrompt.prompt();
                    const { outcome } = await deferredPrompt.userChoice;
                    if (outcome === 'accepted') btn.classList.add('hidden');
                    deferredPrompt = null;
                });
            }

            // En iOS no hay beforeinstallprompt; se indica cómo instalar
            if (btn && /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.navigator.standalone) {
                btn.textContent = 'Abrir en pantalla de inicio (Safari)';
            }
        })();
    </script>
</body>
</html>
