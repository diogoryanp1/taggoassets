<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-slate-800">
@php($currentTenant = app(\App\Domain\Tenancy\CurrentTenant::class)->get())
@php($nav = $currentTenant ? [
    ['group' => 'Visão Geral', 'items' => [['label' => 'Visão geral', 'route' => 'dashboard', 'can' => ['viewAny', \App\Domain\Assets\Models\Asset::class]]]],
    ['group' => 'Patrimônio', 'items' => [['label' => 'Ativos', 'route' => 'assets.index', 'can' => ['viewAny', \App\Domain\Assets\Models\Asset::class]], ['label' => 'Movimentações', 'route' => 'movements.index', 'can' => ['viewAny', \App\Domain\Assets\Models\AssetMovement::class]], ['label' => 'Responsáveis', 'route' => 'custodians.index', 'can' => ['viewAny', \App\Domain\Assets\Models\AssetCustodian::class]], ['label' => 'Categorias', 'route' => 'catalog.categories.index', 'can' => ['viewAny', \App\Domain\Assets\Models\AssetCategory::class]], ['label' => 'Tipos', 'route' => 'catalog.types.index', 'can' => ['viewAny', \App\Domain\Assets\Models\AssetType::class]], ['label' => 'Marcas', 'route' => 'catalog.brands.index', 'can' => ['viewAny', \App\Domain\Assets\Models\AssetBrand::class]], ['label' => 'Modelos', 'route' => 'catalog.models.index', 'can' => ['viewAny', \App\Domain\Assets\Models\AssetModel::class]]]],
    ['group' => 'Estrutura', 'items' => [['label' => 'Unidades administrativas', 'route' => 'units.index', 'can' => ['viewAny', \App\Domain\Organizations\Models\OrganizationalUnit::class]], ['label' => 'Localizações', 'route' => 'locations.index', 'can' => ['viewAny', \App\Domain\Organizations\Models\Location::class]], ['label' => 'Unidades de medida', 'route' => 'catalog.units.index', 'can' => ['viewAny', \App\Domain\Assets\Models\UnitOfMeasure::class]], ['label' => 'Estados de conservação', 'route' => 'catalog.conditions.index', 'can' => ['viewAny', \App\Domain\Assets\Models\AssetCondition::class]]]],
    ['group' => 'Administração', 'items' => [['label' => 'Usuários', 'route' => 'users.index', 'can' => ['viewAny', \App\Models\User::class]], ['label' => 'Convites', 'route' => 'invitations.index', 'can' => ['viewAny', \App\Models\User::class]], ['label' => 'Auditoria', 'route' => 'audit.index', 'can' => ['viewAny', \App\Domain\Audit\Models\AuditLog::class]]]],
    ['group' => 'Configurações', 'items' => [['label' => 'Campos customizados', 'route' => 'catalog.custom-fields.index', 'can' => ['viewAny', \App\Domain\Assets\Models\AssetCustomFieldDefinition::class]]]],
] : [])
<div x-data="{ collapsed: localStorage.getItem('taggo.sidebar.collapsed') === '1', drawer: false, toggle() { this.collapsed = !this.collapsed; localStorage.setItem('taggo.sidebar.collapsed', this.collapsed ? '1' : '0') } }" x-on:keydown.escape.window="drawer = false" class="min-h-screen lg:flex">
    <aside :class="collapsed ? 'lg:w-20' : 'lg:w-72'" class="hidden shrink-0 bg-[#0B1D2D] p-4 text-slate-200 transition-all lg:block">
        <div class="mb-6 flex items-center gap-3">
            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-teal-600 font-bold">T</div>
            <div x-show="!collapsed"><p class="font-[Sora] font-semibold text-white">Taggo Assets</p><p class="text-xs text-teal-200">{{ $currentTenant?->name ?? 'Sem tenant ativo' }}</p></div>
        </div>
        <button type="button" @click="toggle()" class="mb-4 rounded-md px-3 py-2 text-xs hover:bg-slate-800" :aria-label="collapsed ? 'Expandir menu' : 'Recolher menu'"><span x-text="collapsed ? '>>' : '<< Recolher'"></span></button>
        <nav class="space-y-5">
            @foreach($nav as $group)
                <div><p x-show="!collapsed" class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $group['group'] }}</p><div class="space-y-1">@foreach($group['items'] as $item)@can($item['can'][0], $item['can'][1])<a href="{{ route($item['route']) }}" class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs($item['route']) ? 'bg-teal-600 text-white' : 'hover:bg-slate-800' }}" title="{{ $item['label'] }}"><span x-show="!collapsed">{{ $item['label'] }}</span><span x-show="collapsed">{{ mb_substr($item['label'], 0, 1) }}</span></a>@endcan @endforeach</div></div>
            @endforeach
        </nav>
    </aside>
    <div x-show="drawer" x-cloak class="fixed inset-0 z-40 lg:hidden">
        <button type="button" class="absolute inset-0 bg-slate-950/50" @click="drawer = false" aria-label="Fechar menu"></button>
        <aside class="relative h-full w-80 max-w-[85vw] overflow-y-auto bg-[#0B1D2D] p-5 text-slate-200">
            <div class="mb-6 flex items-center justify-between"><div><p class="font-[Sora] font-semibold text-white">Taggo Assets</p><p class="text-xs text-teal-200">{{ $currentTenant?->name ?? 'Sem tenant ativo' }}</p></div><button type="button" @click="drawer = false" class="rounded-md px-3 py-2 text-sm hover:bg-slate-800" aria-label="Fechar menu">Fechar</button></div>
            <nav class="space-y-5">@foreach($nav as $group)<div><p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $group['group'] }}</p><div class="space-y-1">@foreach($group['items'] as $item)@can($item['can'][0], $item['can'][1])<a @click="drawer = false" href="{{ route($item['route']) }}" class="block rounded-md px-3 py-2 text-sm {{ request()->routeIs($item['route']) ? 'bg-teal-600 text-white' : 'hover:bg-slate-800' }}">{{ $item['label'] }}</a>@endcan @endforeach</div></div>@endforeach</nav>
        </aside>
    </div>
    <main class="min-w-0 flex-1">
        <header class="flex min-h-16 items-center justify-between border-b bg-white px-4 sm:px-8">
            <div class="flex items-center gap-3"><button type="button" @click="drawer = true" class="rounded-md px-3 py-2 text-sm lg:hidden" aria-label="Abrir menu">Menu</button><div class="text-sm text-gray-500">@yield('breadcrumb', 'Taggo Assets')</div></div>
            <div class="flex items-center gap-3">@if($currentTenant)<form method="POST" action="{{ route('tenant.update') }}">@csrf @method('PUT')<select name="tenant" onchange="this.form.submit()" class="rounded border-gray-300 py-1 text-sm">@foreach(auth()->user()->tenants as $tenant)<option value="{{ $tenant->public_id }}" @selected($tenant->id === $currentTenant->id)>{{ $tenant->name }}</option>@endforeach</select></form>@endif<span class="hidden text-sm sm:inline">{{ auth()->user()->name }}</span><a class="text-sm text-teal-700" href="{{ route('sessions.index') }}">Sessões</a><form method="POST" action="{{ route('logout') }}">@csrf <button class="text-sm text-teal-700">Sair</button></form></div>
        </header>
        <section class="p-4 sm:p-8">@if(session('success'))<x-alert type="success">{{ session('success') }}</x-alert>@endif {{ $slot }}</section>
    </main>
</div>
</body>
</html>
