<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <h1 class="font-heading text-2xl text-slate-900 text-center mb-1">Zona segura</h1>
        <p class="text-sm text-slate-500 text-center mb-6">Confirma tu contraseña antes de continuar.</p>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div>
                <x-label for="password" value="{{ __('Contraseña') }}" />
                <div class="relative">
                    <i class="fa-solid fa-lock text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                    <x-input id="password" class="pl-11" type="password" name="password" required autocomplete="current-password" autofocus placeholder="••••••••" />
                </div>
            </div>

            <div class="mt-8">
                <x-button class="w-full">
                    <i class="fa-solid fa-check"></i>
                    {{ __('Confirmar') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>