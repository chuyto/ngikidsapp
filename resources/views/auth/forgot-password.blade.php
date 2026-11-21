<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <h1 class="font-heading text-2xl text-slate-900 text-center mb-1">¿Olvidaste tu contraseña?</h1>
        <p class="text-sm text-slate-500 text-center mb-8">Sin problema, indícanos tu correo y te enviaremos un enlace para restablecerla.</p>

        @session('status')
            <div class="mb-4 flex items-center gap-2 font-medium text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-2xl px-4 py-3" role="alert">
                <i class="fa-solid fa-circle-check"></i> {{ $value }}
            </div>
        @endsession

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Correo electrónico') }}" />
                <div class="relative">
                    <i class="fa-solid fa-envelope text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                    <x-input id="email" class="pl-11" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="tucorreo@ejemplo.com" />
                </div>
            </div>

            <div class="mt-8">
                <x-button class="w-full">
                    <i class="fa-solid fa-paper-plane"></i>
                    {{ __('Enviar enlace de recuperación') }}
                </x-button>
            </div>

            <div class="mt-5 text-center">
                <a class="link text-sm" href="{{ route('login') }}">
                    <i class="fa-solid fa-arrow-left"></i> {{ __('Volver al inicio de sesión') }}
                </a>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>