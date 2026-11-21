<div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-cream px-4 overflow-hidden">
    {{-- Fondo decorativo para el efecto vidrio --}}
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-brand-200/70 blur-3xl"></div>
        <div class="absolute top-1/3 -right-32 h-[28rem] w-[28rem] rounded-full bg-amber-100/80 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/4 h-80 w-80 rounded-full bg-emerald-200/60 blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 h-72 w-72 -translate-x-1/2 -translate-y-1/2 rounded-full bg-brand-100/60 blur-3xl"></div>
    </div>

    <div class="relative z-10">
        {{ $logo }}
    </div>

    <div class="relative z-10 w-full sm:max-w-md mt-8 glass rounded-[1.75rem] overflow-hidden py-8 px-8">
        {{ $slot }}
    </div>

    <p class="relative z-10 mt-8 text-xs font-semibold text-slate-400">© 2026 NGI Kids Global · Ministerio Infantil</p>
</div>