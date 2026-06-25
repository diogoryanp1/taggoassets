<x-layouts.app>
    <x-breadcrumb>Dashboard</x-breadcrumb>
    <div class="mb-6"><h1 class="font-[Sora] text-2xl font-bold text-[#0B1D2D]">Visão geral</h1><p class="mt-1 text-sm text-gray-500">Indicadores do tenant atual, atualizados por cache segmentado.</p></div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach(['total_assets' => 'Total de bens', 'in_use' => 'Bens em uso', 'pending_movements' => 'Movimentações pendentes', 'active_loans' => 'Empréstimos ativos', 'upcoming_returns' => 'Retornos próximos', 'overdue_returns' => 'Retornos atrasados', 'without_custodian' => 'Bens sem responsável', 'maintenance' => 'Em manutenção'] as $key => $label)
            <x-card><p class="text-sm text-gray-500">{{ $label }}</p><p class="mt-3 font-[Sora] text-3xl font-bold text-[#0B1D2D]">{{ number_format($metrics[$key]) }}</p></x-card>
        @endforeach
    </div>
    <x-empty-state class="mt-6">Os indicadores carregam apenas contagens agregadas. Use as listagens para analisar ativos e movimentações em detalhe.</x-empty-state>
</x-layouts.app>
