<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Enums\AssetMovementDocumentType;
use App\Domain\Assets\Enums\AssetMovementType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetCustodian;
use App\Domain\Assets\Models\AssetMovement;
use App\Domain\Assets\Services\AssetMovementWorkflowService;
use App\Domain\Assets\Services\AssetTermGenerator;
use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreAssetMovementRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetMovementController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', AssetMovement::class);
        $query = AssetMovement::query()->forTenant($currentTenant->id())->with(['asset:id,public_id,asset_number,description', 'originUnit:id,name', 'destinationUnit:id,name', 'originCustodian:id,name', 'destinationCustodian:id,name', 'requester:id,name', 'approver:id,name']);
        if ($request->filled('asset_number')) {
            $query->whereHas('asset', fn ($asset) => $asset->where('asset_number', 'ilike', '%'.$request->string('asset_number').'%'));
        }
        foreach (['movement_type', 'status'] as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->string($column));
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }
        if ($request->boolean('overdue')) {
            $query->whereNull('returned_at')->whereNotNull('expected_return_at')->where('expected_return_at', '<', now());
        }

        return view('movements.index', ['movements' => $query->latest()->paginate(min($pagination->resolve($request), 100))->withQueryString(), 'types' => AssetMovementType::cases()]);
    }

    public function create(CurrentTenant $currentTenant): View
    {
        $this->authorize('create', AssetMovement::class);

        return view('movements.create', $this->options($currentTenant));
    }

    public function store(StoreAssetMovementRequest $request, CurrentTenant $currentTenant, AssetMovementWorkflowService $workflow): RedirectResponse
    {
        $this->authorize('create', AssetMovement::class);
        $tenant = $currentTenant->require();
        $data = $request->validated();
        $data['asset_id'] = Asset::query()->forTenant($tenant->id)->where('public_id', $data['asset'])->firstOrFail(['id'])->id;
        foreach (['destination_organizational_unit' => OrganizationalUnit::class, 'destination_location' => Location::class, 'destination_custodian' => AssetCustodian::class, 'related_movement' => AssetMovement::class] as $input => $model) {
            if (! empty($data[$input])) {
                $data[$input.'_id'] = $model::query()->forTenant($tenant->id)->where('public_id', $data[$input])->firstOrFail(['id'])->id;
            }
        }
        $movement = $workflow->create($tenant, $request->user(), $data);

        return redirect()->route('movements.show', $movement)->with('success', 'Movimentação registrada com sucesso.');
    }

    public function show(AssetMovement $movement, CurrentTenant $currentTenant): View
    {
        abort_unless($movement->tenant_id === $currentTenant->id(), 404);
        $this->authorize('view', $movement);
        $movement->load(['asset.brand', 'asset.model', 'originUnit', 'destinationUnit', 'originLocation', 'destinationLocation', 'originCustodian', 'destinationCustodian', 'requester', 'approver', 'termDocument', 'documents.document', 'documents.uploader']);

        return view('movements.show', ['movement' => $movement, 'documentTypes' => array_filter(AssetMovementDocumentType::cases(), fn (AssetMovementDocumentType $type): bool => $type !== AssetMovementDocumentType::GeneratedTerm)]);
    }

    public function approve(AssetMovement $movement, CurrentTenant $currentTenant, AssetMovementWorkflowService $workflow): RedirectResponse
    {
        abort_unless($movement->tenant_id === $currentTenant->id(), 404);
        $this->authorize('approve', $movement);
        $workflow->approve($movement, request()->user());

        return back()->with('success', 'Movimentação aprovada.');
    }

    public function reject(Request $request, AssetMovement $movement, CurrentTenant $currentTenant, AssetMovementWorkflowService $workflow): RedirectResponse
    {
        abort_unless($movement->tenant_id === $currentTenant->id(), 404);
        $this->authorize('reject', $movement);
        $workflow->reject($movement, $request->user(), $request->input('reason'));

        return back()->with('success', 'Movimentação rejeitada.');
    }

    public function cancel(Request $request, AssetMovement $movement, CurrentTenant $currentTenant, AssetMovementWorkflowService $workflow): RedirectResponse
    {
        abort_unless($movement->tenant_id === $currentTenant->id(), 404);
        $this->authorize('cancel', $movement);
        $workflow->cancel($movement, $request->user(), $request->input('reason'));

        return back()->with('success', 'Movimentação cancelada.');
    }

    public function complete(AssetMovement $movement, CurrentTenant $currentTenant, AssetMovementWorkflowService $workflow): RedirectResponse
    {
        abort_unless($movement->tenant_id === $currentTenant->id(), 404);
        $this->authorize('complete', $movement);
        $workflow->complete($movement, request()->user());

        return back()->with('success', 'Movimentação concluída.');
    }

    public function generateTerm(AssetMovement $movement, CurrentTenant $currentTenant, AssetTermGenerator $generator, AuditLogger $audit): RedirectResponse
    {
        abort_unless($movement->tenant_id === $currentTenant->id(), 404);
        $this->authorize('generateTerm', $movement);
        $document = $generator->generate($movement, request()->user());
        $audit->record('asset_term.generated', $movement, [], ['document' => $document->public_id]);

        return redirect()->route('documents.download', $document)->with('success', 'Termo gerado com sucesso.');
    }

    private function options(CurrentTenant $currentTenant): array
    {
        $tenant = $currentTenant->require();

        return [
            'assets' => Asset::query()->forTenant($tenant->id)->where('is_active', true)->with(['organizationalUnit:id,name', 'location:id,name', 'custodian:id,name'])->orderBy('asset_number')->get(['id', 'public_id', 'asset_number', 'description', 'organizational_unit_id', 'location_id', 'custodian_id']),
            'types' => AssetMovementType::operationalCases(),
            'organizationalUnits' => OrganizationalUnit::query()->forTenant($tenant->id)->where('status', 'active')->orderBy('name')->get(['id', 'public_id', 'name']),
            'locations' => Location::query()->forTenant($tenant->id)->where('status', 'active')->orderBy('name')->get(['id', 'public_id', 'organizational_unit_id', 'name']),
            'custodians' => AssetCustodian::query()->forTenant($tenant->id)->where('is_active', true)->orderBy('name')->get(['id', 'public_id', 'organizational_unit_id', 'name']),
            'openReturns' => AssetMovement::query()->forTenant($tenant->id)->whereIn('movement_type', [AssetMovementType::TemporaryCheckout->value, AssetMovementType::Loan->value])->whereNull('returned_at')->with('asset:id,asset_number')->latest()->get(['id', 'public_id', 'asset_id', 'movement_type']),
        ];
    }
}
