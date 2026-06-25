<x-layouts.app>
    <x-breadcrumb>Patrimônio / Responsáveis</x-breadcrumb>
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="font-[Sora] text-2xl font-bold">Responsáveis patrimoniais</h1><p class="text-sm text-text-secondary">Pessoas responsáveis por guarda, empréstimo ou uso de ativos.</p></div>
        @can('create', App\Domain\Assets\Models\AssetCustodian::class)<x-button type="button" onclick="location.href='{{ route('custodians.create') }}'">Novo responsável</x-button>@endcan
    </div>
    <x-filter-panel method="GET"><x-input name="name" label="Responsável" value="{{ request('name') }}" /><x-select name="is_active" label="Situação"><option value="">Todas</option><option value="1" @selected(request('is_active')==='1')>Ativo</option><option value="0" @selected(request('is_active')==='0')>Inativo</option></x-select><x-button>Aplicar filtros</x-button><a class="px-3 py-2 text-sm text-teal-700" href="{{ route('custodians.index') }}">Limpar filtros</a></x-filter-panel>
    <x-card class="mt-5"><x-table><thead><tr><th class="p-3">Responsável</th><th>Unidade administrativa</th><th>Matrícula</th><th>Ativos</th><th>Situação</th><th></th></tr></thead><tbody>
        @forelse($custodians as $custodian)<tr><td class="p-3"><div class="font-medium">{{ $custodian->name }}</div><div class="text-xs text-text-secondary">{{ $custodian->email ?? 'E-mail não informado' }}</div></td><td>{{ $custodian->organizationalUnit->name }}</td><td>{{ $custodian->registration_number ?? '-' }}</td><td>{{ $custodian->assets_count }}</td><td><x-badge>{{ $custodian->is_active ? 'Ativo' : 'Inativo' }}</x-badge></td><td class="text-right"><a class="text-sm text-teal-700" href="{{ route('custodians.edit', $custodian) }}">Editar</a></td></tr>@empty<tr><td colspan="6" class="p-8"><x-empty-state>Nenhum responsável encontrado. Cadastre responsáveis para registrar atribuições, transferências e empréstimos.</x-empty-state></td></tr>@endforelse
    </tbody>
    </x-table><x-pagination :paginator="$custodians" /></x-card>
</x-layouts.app>
