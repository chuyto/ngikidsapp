@props([
    'action',
    'placeholder' => 'Buscar...',
    'sorts' => [],
])

<form method="GET" action="{{ $action }}" class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col lg:flex-row gap-3" role="search">
    <div class="relative flex-1">
        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
        <label for="table-search-{{ md5($action) }}" class="sr-only">Buscar registros</label>
        <input id="table-search-{{ md5($action) }}" name="search" value="{{ request('search') }}"
               class="input pl-11" placeholder="{{ $placeholder }}" autocomplete="off">
    </div>

    @if(count($sorts))
        <label class="sr-only" for="table-sort-{{ md5($action) }}">Ordenar por</label>
        <select id="table-sort-{{ md5($action) }}" name="sort" class="input lg:w-52">
            @foreach($sorts as $value => $label)
                <option value="{{ $value }}" @selected(request('sort') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    @endif

    <label class="sr-only" for="table-per-page-{{ md5($action) }}">Registros por página</label>
    <select id="table-per-page-{{ md5($action) }}" name="per_page" class="input lg:w-36">
        @foreach([15, 30, 50] as $size)
            <option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }} por página</option>
        @endforeach
    </select>

    <button class="btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Aplicar</button>
    @if(request()->filled('search') || request()->filled('sort') || request()->filled('per_page'))
        <a class="btn-ghost" href="{{ $action }}"><i class="fa-solid fa-xmark"></i> Limpiar</a>
    @endif
</form>
