<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <h1 class="font-heading text-2xl text-slate-900 text-center mb-1">Bienvenido de nuevo</h1>
        <p class="text-sm text-slate-500 text-center mb-8">Ingresa con tu cuenta para continuar</p>

        <x-validation-errors class="mb-4" />

        @session('status')
            <div class="mb-4 flex items-center gap-2 font-medium text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-2xl px-4 py-3" role="alert">
                <i class="fa-solid fa-circle-check"></i> {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Correo electrónico') }}" />
                <div class="relative">
                    <i class="fa-solid fa-envelope text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                    <x-input id="email" class="pl-11" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="tucorreo@ejemplo.com" />
                </div>
            </div>

            <div class="mt-5">
                <x-label for="password" value="{{ __('Contraseña') }}" />
                <div class="relative">
                    <i class="fa-solid fa-lock text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 text-sm"></i>
                    <x-input id="password" class="pl-11" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                </div>
            </div>

            <div class="flex items-center justify-between mt-5">
                <label for="remember_me" class="flex items-center cursor-pointer select-none">
                    <x-checkbox id="remember_me" name="remember" class="rounded-md border-slate-300 text-brand-600 focus:ring-brand-500" />
                    <span class="ms-2 text-sm font-semibold text-slate-600">{{ __('Recordarme') }}</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="link text-sm" href="{{ route('password.request') }}">
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </a>
                @endif
            </div>

            <div class="mt-8">
                <x-button class="w-full">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    {{ __('Iniciar sesión') }}
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>