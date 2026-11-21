<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="text-center mb-6">
            <div class="inline-flex p-4 bg-brand-50 text-brand-500 rounded-2xl mb-4">
                <i class="fa-solid fa-shield-halved text-3xl"></i>
            </div>
            <h1 class="font-heading text-2xl text-slate-900">Verificación en dos pasos</h1>
        </div>

        <div x-data="{ recovery: false }">
            <div class="mb-4 text-sm font-semibold text-slate-600" x-show="! recovery">
                {{ __('Ingresa el código de autenticación de tu aplicación de seguridad.') }}
            </div>

            <div class="mb-4 text-sm font-semibold text-slate-600" x-cloak x-show="recovery">
                {{ __('Ingresa uno de tus códigos de recuperación de emergencia.') }}
            </div>

            <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ route('two-factor.login') }}">
                @csrf

                <div class="mt-4" x-show="! recovery">
                    <x-label for="code" value="{{ __('Código') }}" />
                    <x-input id="code" class="mt-1 block w-full" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code" placeholder="000000" />
                </div>

                <div class="mt-4" x-cloak x-show="recovery">
                    <x-label for="recovery_code" value="{{ __('Código de recuperación') }}" />
                    <x-input id="recovery_code" class="mt-1 block w-full" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code" />
                </div>

                <div class="mt-8 flex items-center justify-between gap-3">
                    <button type="button" class="link text-sm cursor-pointer"
                                    x-show="! recovery"
                                    x-on:click="
                                        recovery = true;
                                        $nextTick(() => { $refs.recovery_code.focus() })
                                    ">
                        {{ __('Usar un código de recuperación') }}
                    </button>

                    <button type="button" class="link text-sm cursor-pointer"
                                    x-cloak
                                    x-show="recovery"
                                    x-on:click="
                                        recovery = false;
                                        $nextTick(() => { $refs.code.focus() })
                                    ">
                        {{ __('Usar un código de autenticación') }}
                    </button>

                    <x-button type="submit">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        {{ __('Entrar') }}
                    </x-button>
                </div>
            </form>
        </div>
    </x-authentication-card>
</x-guest-layout>