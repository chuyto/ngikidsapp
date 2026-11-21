@if ($errors->any())
    <div {{ $attributes }}>
        <div class="mb-3 flex items-center gap-2 font-bold text-sm text-brand-700 bg-brand-50 border border-brand-200 rounded-2xl px-4 py-3" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i> {{ __('Ups! Algo salió mal.') }}
        </div>

        <ul class="mb-4 list-disc list-inside text-sm font-semibold text-brand-600 bg-brand-50/60 border border-brand-100 rounded-2xl px-5 py-4">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif