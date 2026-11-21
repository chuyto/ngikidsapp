<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <h1 class="font-heading text-2xl text-slate-900 text-center mb-1">{{ __('Restablecer contraseña') }}</h1>
        <p class="text-sm text-slate-500 text-center mb-6">Define tu nueva clave de acceso.</p>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <x-label for="email" value="{{ __('Correo electrónico') }}" />
                <div class="relative">
                    <i class="fa-solid fa-envelope text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                    <x-input id="email" class="pl-11" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
                </div>
            </div>

            <div class="mt-5">
                <x-label for="password" value="{{ __('Nueva contraseña') }}" />
                <div class="relative">
                    <i class="fa-solid fa-lock text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                    <x-input id="password" class="pl-11" type="password" name="password" required autocomplete="new-password" placeholder="••••••••" />
                </div>
            </div>

            <div class="mt-5">
                <x-label for="password_confirmation" value="{{ __('Confirmar contraseña') }}" />
                <div class="relative">
                    <i class="fa-solid fa-lock text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                    <x-input id="password_confirmation" class="pl-11" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="••••••••" />
                </div>
            </div>

            <div class="mt-8">
                <x-button class="w-full">
                    <i class="fa-solid fa-rotate"></i>
                    {{ __('Restablecer contraseña') }}
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