<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 12px; line-height: 1.45; }
        h1 { color: #0b1d2d; font-size: 22px; margin: 0 0 4px; }
        h2 { color: #0b1d2d; font-size: 14px; margin: 22px 0 8px; border-bottom: 1px solid #d8dee8; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { width: 31%; color: #526070; text-align: left; font-weight: normal; padding: 5px 8px; border-bottom: 1px solid #edf0f5; }
        td { padding: 5px 8px; border-bottom: 1px solid #edf0f5; }
        .muted { color: #526070; }
        .box { border: 1px solid #d8dee8; border-radius: 6px; padding: 12px; margin-top: 12px; }
        .signatures { margin-top: 44px; display: table; width: 100%; table-layout: fixed; }
        .signature { display: table-cell; text-align: center; padding: 0 18px; }
        .line { border-top: 1px solid #172033; padding-top: 7px; }
    </style>
</head>
<body>
    <p class="muted">Taggo Assets</p>
    <h1>{{ $title }}</h1>
    <p class="muted">Gerado em {{ $generatedAt->format('d/m/Y H:i') }} | Movimentação {{ $movement->public_id }}</p>

    <h2>Identificação</h2>
    <table>
        <tr><th>Tenant</th><td>{{ $movement->tenant->name }}</td></tr>
        <tr><th>Tipo de movimentação</th><td>{{ $movement->movementType()->label() }}</td></tr>
        <tr><th>Status</th><td>{{ $movement->movementStatus()->label() }}</td></tr>
        <tr><th>Solicitante</th><td>{{ $movement->requester->name }}</td></tr>
        <tr><th>Aprovador</th><td>{{ $movement->approver?->name ?? '-' }}</td></tr>
    </table>

    <h2>Bem patrimonial</h2>
    <table>
        <tr><th>Número patrimonial</th><td>{{ $movement->asset->asset_number }}</td></tr>
        <tr><th>Descrição</th><td>{{ $movement->asset->description }}</td></tr>
        <tr><th>Categoria</th><td>{{ $movement->asset->category->name }}</td></tr>
        <tr><th>Tipo</th><td>{{ $movement->asset->type->name }}</td></tr>
        <tr><th>Marca e modelo</th><td>{{ $movement->asset->brand?->name ?? '-' }} / {{ $movement->asset->model?->name ?? '-' }}</td></tr>
        <tr><th>Número de série</th><td>{{ $movement->asset->serial_number ?? '-' }}</td></tr>
    </table>

    <h2>Origem e destino</h2>
    <table>
        <tr><th>Unidade de origem</th><td>{{ $movement->originUnit?->name ?? '-' }}</td></tr>
        <tr><th>Localização de origem</th><td>{{ $movement->originLocation?->name ?? '-' }}</td></tr>
        <tr><th>Responsável anterior</th><td>{{ $movement->originCustodian?->name ?? '-' }}</td></tr>
        <tr><th>Unidade de destino</th><td>{{ $movement->destinationUnit?->name ?? '-' }}</td></tr>
        <tr><th>Localização de destino</th><td>{{ $movement->destinationLocation?->name ?? '-' }}</td></tr>
        <tr><th>Novo responsável</th><td>{{ $movement->destinationCustodian?->name ?? '-' }}</td></tr>
    </table>

    <h2>Prazos e justificativa</h2>
    <table>
        <tr><th>Data efetiva</th><td>{{ $movement->effective_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
        <tr><th>Retorno previsto</th><td>{{ $movement->expected_return_at?->format('d/m/Y H:i') ?? '-' }}</td></tr>
        <tr><th>Motivo</th><td>{{ $movement->reason }}</td></tr>
        <tr><th>Observações</th><td>{{ $movement->notes ?? '-' }}</td></tr>
    </table>

    <div class="box">
        {{ $slot }}
    </div>

    <div class="signatures">
        <div class="signature"><div class="line">Responsável</div></div>
        <div class="signature"><div class="line">Administração patrimonial</div></div>
    </div>
</body>
</html>
