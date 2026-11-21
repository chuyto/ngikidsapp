<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <div class="p-2.5 bg-brand-100 text-brand-600 rounded-2xl">
                <i class="fa-solid fa-shield-halved text-xl"></i>
            </div>
            <div>
                <h2 class="page-title leading-tight">{{ __('Aviso de Privacidad') }}</h2>
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">NGI Kids · Datos personales</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <div class="card p-8 space-y-6 text-sm leading-relaxed text-slate-600 font-medium">
            <p>
                <strong class="text-slate-800">Responsable:</strong> NGI Kids
                (contacto: ngikidsglobal@zohomail.com). Los datos se recaban
                directamente del padre, madre o tutor al momento del registro.
            </p>

            <div>
                <h3 class="font-heading text-base text-slate-900 mb-2">1. Datos que recabamos</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Del responsable: nombre completo, parentesco, teléfono y correo electrónico (opcional).</li>
                    <li>De los menores: nombre completo y fecha de nacimiento (para calcular la edad).</li>
                    <li>Firma manuscrita al registrar entradas y salidas (evidencia de entrega).</li>
                </ul>
            </div>

            <div>
                <h3 class="font-heading text-base text-slate-900 mb-2">2. Finalidades</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Registro y control de asistencia a los servicios y actividades.</li>
                    <li>Emisión de la credencial digital (Apple Wallet / Google Wallet).</li>
                    <li>Contacto operativo (avisos, entregas y seguimiento).</li>
                </ul>
                <p class="mt-2">Tus datos <strong class="text-slate-800">no</strong> se venden ni se comparten con terceros con fines publicitarios.</p>
            </div>

            <div>
                <h3 class="font-heading text-base text-slate-900 mb-2">3. Menores de edad</h3>
                <p>
                    Los datos de niñas y niños solo se recaban con el consentimiento
                    de quien ejerce la patria potestad o tutela, otorgado al aceptar
                    este aviso en el registro. Se usan exclusivamente para las
                    finalidades descritas.
                </p>
            </div>

            <div>
                <h3 class="font-heading text-base text-slate-900 mb-2">4. Conservación y seguridad</h3>
                <p>
                    Los datos se conservan mientras la familia participe en el ministerio
                    y se eliminan o anonimizar al dejar de ser necesarios. Aplicamos
                    controles de acceso por roles y los respaldos del proveedor de
                    hospedaje. Las credenciales digitales pueden revocarse en cualquier
                    momento solicitándolo al personal.
                </p>
            </div>

            <div>
                <h3 class="font-heading text-base text-slate-900 mb-2">5. Derechos ARCO</h3>
                <p>
                    Puedes solicitar el Acceso, Rectificación, Cancelación u Oposición
                    sobre tus datos (y los de tus hijos) escribiendo a
                    <strong class="text-slate-800">ngikidsglobal@zohomail.com</strong>.
                    Atendemos tu solicitud en un máximo de 20 días hábiles.
                </p>
            </div>

            <p class="text-xs text-slate-400 font-semibold pt-2 border-t border-slate-100">
                Última actualización: octubre 2026. Al marcar la casilla de aceptación
                en el registro queda constancia de la fecha y hora de tu consentimiento.
            </p>
        </div>
    </div>
</x-app-layout>
