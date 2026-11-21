@props(['for'])

@error($for)
    <p {{ $attributes->merge(['class' => 'mt-1.5 flex items-center gap-1.5 text-sm font-semibold text-brand-600']) }} role="alert">
        <i class="fa-solid fa-circle-exclamation text-xs"></i>{{ $message }}
    </p>
@enderror