@props(['title', 'icon' => 'fa-plus', 'trigger' => 'AGREGAR NUEVO'])

<div x-data="{
        open: false,
        submitEvent(event) {
            const form = event.target;
            if (!form || !form.matches('form[data-modal-form]')) return;

            event.preventDefault();
            const submitBtn = form.querySelector('button[type=submit]');
            const originalContent = submitBtn ? submitBtn.innerHTML : '';

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Guardando...';
            }

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: new FormData(form),
            })
            .then(async res => {
                if (res.redirected || (res.ok && !res.headers.get('content-type')?.includes('json'))) {
                    return { ok: true, message: 'Registro guardado correctamente.' };
                }
                if (res.status === 422) {
                    const data = await res.json();
                    const errors = data.errors || {};
                    return { ok: false, message: Object.values(errors).flat()[0] || 'Revisa los datos del formulario.' };
                }
                const data = await res.json().catch(() => null);
                if (res.ok) {
                    return { ok: true, message: data?.success || 'Registro guardado correctamente.', redirect: data?.redirect };
                }
                return { ok: false, message: data?.error || 'Error al guardar el registro.' };
            })
            .then(result => {
                if (!result) return;
                window.showAlert(result.message, !result.ok);
                if (result.ok) {
                    setTimeout(() => result.redirect ? window.location.assign(result.redirect) : window.location.reload(), 700);
                }
            })
            .catch(() => window.showAlert('Error de conexión al guardar.', true))
            .finally(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalContent;
                }
            });
        },
    }">

    <button type="button" @click="open = true; $nextTick(() => $refs.dialog.querySelector('input, select, textarea')?.focus())"
            :aria-expanded="open" class="{{ $attributes->get('buttonClass') ?? 'btn-primary w-full sm:w-auto' }}">
        <i class="fa-solid {{ $icon }}"></i>
        {{ $trigger }}
    </button>

    <template x-teleport="body">
        <div x-cloak x-show="open"
             x-transition.opacity.duration.200ms
             class="fixed inset-0 z-[90] flex items-center justify-center p-4 sm:p-6 bg-slate-900/40 backdrop-blur-sm"
             @keydown.escape.window="open = false"
             @submit="submitEvent($event)">

            <div class="absolute inset-0" @click="open = false" aria-hidden="true"></div>

            <div x-ref="dialog" role="dialog" aria-modal="true" aria-label="{{ $title }}"
                 class="relative w-full max-w-2xl max-h-[90vh] bg-white rounded-3xl shadow-2xl overflow-hidden flex flex-col">
                {{-- Encabezado del Modal --}}
                <div class="flex items-center justify-between px-8 py-5 border-b border-slate-100 bg-slate-50/60 flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                            <i class="fa-solid {{ $icon }}"></i>
                        </div>
                        <h3 class="font-heading text-lg text-slate-900 leading-tight">{{ $title }}</h3>
                    </div>
                    <button type="button" @click="open = false"
                            class="w-9 h-9 flex items-center justify-center rounded-full bg-white border border-slate-200 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors cursor-pointer"
                            aria-label="Cerrar">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="p-6 sm:p-8 overflow-y-auto">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </template>
</div>
