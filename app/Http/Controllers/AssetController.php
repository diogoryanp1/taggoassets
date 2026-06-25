<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Assets\Models\AssetModel;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Assets\Models\UnitOfMeasure;
use App\Domain\Assets\Services\AssetWriter;
use App\Domain\Audit\AuditLogger;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreAssetRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', Asset::class);
        $filterKeys = ['asset_number', 'legacy_number', 'description', 'category', 'type', 'brand', 'model', 'organizational_unit', 'location', 'status', 'condition', 'serial_number', 'acquisition_date_from', 'acquisition_date_to', 'is_active'];
        $filtered = collect($filterKeys)->contains(fn (string $key): bool => $request->filled($key));
        $assets = null;
        if ($filtered) {
            $query = Asset::query()->forTenant($currentTenant->id())->select(['id', 'public_id', 'asset_number', 'legacy_number', 'description', 'asset_category_id', 'asset_type_id', 'brand_id', 'model_id', 'unit_of_measure_id', 'condition_id', 'status', 'organizational_unit_id', 'location_id', 'serial_number', 'acquisition_date', 'is_active', 'created_at'])->with(['category:id,name', 'type:id,name', 'brand:id,name', 'model:id,name', 'unitOfMeasure:id,name,symbol', 'condition:id,name', 'organizationalUnit:id,name', 'location:id,name']);
            foreach (['asset_number', 'legacy_number', 'description', 'serial_number'] as $column) {
                if ($request->filled($column)) {
                    $query->where($column, 'ilike', '%'.$request->string($column).'%');
                }
            }
            foreach (['category' => 'category', 'type' => 'type', 'brand' => 'brand', 'model' => 'model', 'condition' => 'condition', 'organizational_unit' => 'organizationalUnit', 'location' => 'location'] as $input => $relation) {
                if ($request->filled($input)) {
                    $query->whereHas($relation, fn ($query) => $query->where('public_id', $request->string($input)));
                }
            }
            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }
            if ($request->filled('acquisition_date_from')) {
                $query->whereDate('acquisition_date', '>=', $request->date('acquisition_date_from'));
            }
            if ($request->filled('acquisition_date_to')) {
                $query->whereDate('acquisition_date', '<=', $request->date('acquisition_date_to'));
            }
            $assets = $query->latest()->paginate(min($pagination->resolve($request), 100))->withQueryString();
        }

        return view('assets.index', ['assets' => $assets, 'filtered' => $filtered, ...$this->filterOptions($currentTenant)]);
    }

    public function create(CurrentTenant $currentTenant): View
    {
        $this->authorize('create', Asset::class);

        return view('assets.create', $this->formOptions($currentTenant));
    }

    public function store(StoreAssetRequest $request, CurrentTenant $currentTenant, AssetWriter $writer, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', Asset::class);
        $tenant = $currentTenant->require();
        $asset = $writer->create($tenant, $request->user(), $request->validated(), $request->user()->hasPermission($tenant, 'assets.set_manual_number'));
        $audit->record('asset.created', $asset, [], $asset->only(['asset_number', 'description', 'status']));
        if (! $request->filled('asset_number') || ! $request->user()->hasPermission($tenant, 'assets.set_manual_number')) {
            $audit->record('asset.number_generated', $asset, [], ['asset_number' => $asset->asset_number]);
        }
        Cache::forget("tenant:{$tenant->id}:dashboard:summary");

        return redirect()->route('assets.index', ['asset_number' => $asset->asset_number])->with('success', 'Ativo cadastrado com sucesso.');
    }

    public function edit(Asset $asset, CurrentTenant $currentTenant): View
    {
        abort_unless($asset->tenant_id === $currentTenant->id(), 404);
        $this->authorize('update', $asset);
        $asset->load(['category:id,public_id', 'type:id,public_id', 'brand:id,public_id', 'model:id,public_id', 'unitOfMeasure:id,public_id', 'condition:id,public_id', 'organizationalUnit:id,public_id', 'location:id,public_id']);

        return view('assets.edit', ['asset' => $asset, ...$this->formOptions($currentTenant)]);
    }

    public function show(Asset $asset, CurrentTenant $currentTenant): View
    {
        abort_unless($asset->tenant_id === $currentTenant->id(), 404);
        $this->authorize('viewAny', Asset::class);
        $asset->load(['category:id,name', 'type:id,name', 'brand:id,name', 'model:id,name', 'unitOfMeasure:id,name,symbol', 'condition:id,name', 'organizationalUnit:id,name', 'location:id,name', 'custodian:id,name', 'creator:id,name']);
        $movements = $asset->movements()->forTenant($currentTenant->id())->with(['originUnit:id,name', 'destinationUnit:id,name', 'originCustodian:id,name', 'destinationCustodian:id,name', 'requester:id,name', 'approver:id,name'])->latest()->paginate(10, ['*'], 'movements_page')->withQueryString();
        $auditLogs = AuditLog::query()->where('tenant_id', $currentTenant->id())->where('entity_type', Asset::class)->where('entity_id', $asset->id)->latest('created_at')->limit(5)->get();

        return view('assets.show', compact('asset', 'auditLogs', 'movements'));
    }

    public function update(StoreAssetRequest $request, Asset $asset, CurrentTenant $currentTenant, AssetWriter $writer, AuditLogger $audit): RedirectResponse
    {
        abort_unless($asset->tenant_id === $currentTenant->id(), 404);
        $this->authorize('update', $asset);
        $old = $asset->only(['asset_number', 'description', 'status']);
        $updated = $writer->update($asset, $currentTenant->require(), $request->user(), $request->validated(), $request->user()->hasPermission($currentTenant->require(), 'assets.set_manual_number'));
        $audit->record('asset.updated', $updated, $old, $updated->only(['asset_number', 'description', 'status']));
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return redirect()->route('assets.index', ['asset_number' => $updated->asset_number])->with('success', 'Ativo atualizado com sucesso.');
    }

    public function deactivate(Asset $asset, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        abort_unless($asset->tenant_id === $currentTenant->id(), 404);
        $this->authorize('deactivate', $asset);
        $old = $asset->only(['is_active', 'status']);
        DB::transaction(fn () => $asset->update(['is_active' => false, 'status' => 'inactive']));
        $audit->record('asset.deactivated', $asset, $old, $asset->only(['is_active', 'status']));
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return back()->with('success', 'Ativo inativado com sucesso.');
    }

    public function reactivate(Asset $asset, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        abort_unless($asset->tenant_id === $currentTenant->id(), 404);
        $this->authorize('deactivate', $asset);
        $old = $asset->only(['is_active', 'status']);
        DB::transaction(fn () => $asset->update(['is_active' => true, 'status' => 'active']));
        $audit->record('asset.reactivated', $asset, $old, $asset->only(['is_active', 'status']));
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return back()->with('success', 'Ativo reativado com sucesso.');
    }

    private function formOptions(CurrentTenant $currentTenant): array
    {
        $tenant = $currentTenant->require();

        return [
            'categories' => AssetCategory::query()->forTenant($tenant->id)->where('is_active', true)->orderBy('name')->get(['id', 'public_id', 'name']),
            'types' => AssetType::query()->forTenant($tenant->id)->where('is_active', true)->orderBy('name')->get(['id', 'public_id', 'asset_category_id', 'name']),
            'brands' => AssetBrand::query()->forTenant($tenant->id)->where('is_active', true)->orderBy('name')->get(['id', 'public_id', 'name']),
            'models' => AssetModel::query()->forTenant($tenant->id)->where('is_active', true)->orderBy('name')->get(['id', 'public_id', 'asset_brand_id', 'asset_type_id', 'name']),
            'units' => UnitOfMeasure::query()->availableToTenant($tenant->id)->where('is_active', true)->orderBy('name')->get(['id', 'public_id', 'name', 'symbol']),
            'conditions' => AssetCondition::query()->availableToTenant($tenant->id)->where('is_active', true)->orderBy('sort_order')->get(['id', 'public_id', 'name']),
            'organizationalUnits' => OrganizationalUnit::query()->forTenant($tenant->id)->where('status', 'active')->orderBy('name')->get(['id', 'public_id', 'name']),
        ];
    }

    private function filterOptions(CurrentTenant $currentTenant): array
    {
        $tenant = $currentTenant->require();

        return ['categories' => AssetCategory::query()->forTenant($tenant->id)->orderBy('name')->get(['id', 'public_id', 'name']), 'types' => AssetType::query()->forTenant($tenant->id)->orderBy('name')->get(['id', 'public_id', 'name']), 'brands' => AssetBrand::query()->forTenant($tenant->id)->orderBy('name')->get(['id', 'public_id', 'name']), 'models' => AssetModel::query()->forTenant($tenant->id)->orderBy('name')->get(['id', 'public_id', 'name']), 'conditions' => AssetCondition::query()->availableToTenant($tenant->id)->orderBy('sort_order')->get(['id', 'public_id', 'name']), 'organizationalUnits' => OrganizationalUnit::query()->forTenant($tenant->id)->orderBy('name')->get(['id', 'public_id', 'name'])];
    }
}
