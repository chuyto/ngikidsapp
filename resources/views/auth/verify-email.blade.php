<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="text-center mb-6">
            <div class="inline-flex p-4 bg-emerald-100 text-emerald-600 rounded-2xl mb-4">
                <i class="fa-solid fa-envelope-circle-check text-3xl"></i>
            </div>
            <h1 class="font-heading text-2xl text-slate-900">Verifica tu correo</h1>
            <p class="text-sm text-slate-500 mt-1">Antes de continuar, confirma tu dirección de correo con el enlace que te enviamos.</p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="mb-4 flex items-center gap-2 font-medium text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-2xl px-4 py-3" role="alert">
                <i class="fa-solid fa-circle-check"></i> {{ __('Se ha enviado un nuevo enlace de verificación a tu correo.') }}
            </div>
        @endif

        <div class="mt-6 flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <x-button class="w-full">
                    <i class="fa-solid fa-paper-plane"></i>
                    {{ __('Reenviar correo de verificación') }}
                </x-button>
            </form>

            <div class="flex items-center justify-center gap-4 pt-2">
                <a href="{{ route('profile.show') }}" class="link text-sm">
                    <i class="fa-solid fa-user-pen"></i> {{ __('Editar perfil') }}
                </a>

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="link text-sm">
                        <i class="fa-solid fa-right-from-bracket"></i> {{ __('Cerrar sesión') }}
                    </button>
                </form>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>