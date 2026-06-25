<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Assets\Models\AssetModel;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreAssetModelRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetModelController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', AssetModel::class);
        $query = AssetModel::query()->forTenant($currentTenant->id())->with(['brand:id,name', 'type:id,name'])->orderBy('name');
        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%'.$request->string('search').'%');
        }
        if ($request->filled('brand')) {
            $query->whereHas('brand', fn ($query) => $query->where('public_id', $request->string('brand')));
        }
        if ($request->filled('type')) {
            $query->whereHas('type', fn ($query) => $query->where('public_id', $request->string('type')));
        }
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return view('catalog.models.index', ['models' => $query->paginate($pagination->resolve($request))->withQueryString(), ...$this->options($currentTenant)]);
    }

    public function create(CurrentTenant $currentTenant): View
    {
        $this->authorize('create', AssetModel::class);

        return view('catalog.models.create', $this->options($currentTenant));
    }

    public function store(StoreAssetModelRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', AssetModel::class);
        $tenant = $currentTenant->require();
        $payload = $this->payload($request->validated(), $tenant->id);
        $this->rejectDuplicate($payload, $tenant->id);
        $model = DB::transaction(fn () => AssetModel::forceCreate($payload));
        $audit->record('asset_model.created', $model, [], $model->only(['name', 'manufacturer_code', 'asset_brand_id', 'asset_type_id', 'is_active']));
        Cache::forget("tenant:{$tenant->id}:dashboard:summary");

        return redirect()->route('catalog.models.show', $model)->with('success', 'Modelo cadastrado com sucesso.');
    }

    public function show(AssetModel $model, CurrentTenant $currentTenant): View
    {
        $this->guardTenant($model, $currentTenant);
        $this->authorize('viewAny', AssetModel::class);
        $model->load(['brand:id,name', 'type:id,name']);

        return view('catalog.models.show', compact('model'));
    }

    public function edit(AssetModel $model, CurrentTenant $currentTenant): View
    {
        $this->guardTenant($model, $currentTenant);
        $this->authorize('update', $model);

        return view('catalog.models.edit', ['model' => $model, ...$this->options($currentTenant)]);
    }

    public function update(StoreAssetModelRequest $request, AssetModel $model, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->guardTenant($model, $currentTenant);
        $this->authorize('update', $model);
        $payload = $this->payload($request->validated(), $currentTenant->id());
        $this->rejectDuplicate($payload, $currentTenant->id(), $model->id);
        $old = $model->only(['name', 'manufacturer_code', 'asset_brand_id', 'asset_type_id', 'is_active']);
        DB::transaction(fn () => $model->update($payload));
        $audit->record('asset_model.updated', $model, $old, $model->only(array_keys($old)));
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return redirect()->route('catalog.models.show', $model)->with('success', 'Modelo atualizado com sucesso.');
    }

    public function deactivate(AssetModel $model, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($model, $currentTenant, $audit, false, 'asset_model.deactivated', 'Modelo inativado com sucesso.');
    }

    public function reactivate(AssetModel $model, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($model, $currentTenant, $audit, true, 'asset_model.reactivated', 'Modelo reativado com sucesso.');
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function payload(array $data, int $tenantId): array
    {
        $brand = AssetBrand::query()->forTenant($tenantId)->where('public_id', $data['asset_brand_id'])->firstOrFail();
        $type = ! empty($data['asset_type_id']) ? AssetType::query()->forTenant($tenantId)->where('public_id', $data['asset_type_id'])->firstOrFail() : null;
        $name = Str::squish((string) $data['name']);

        return ['tenant_id' => $tenantId, 'asset_brand_id' => $brand->id, 'asset_type_id' => $type?->id, 'name' => $name, 'name_normalized' => Str::lower($name), 'manufacturer_code' => filled($data['manufacturer_code'] ?? null) ? Str::upper(Str::squish((string) $data['manufacturer_code'])) : null, 'description' => $data['description'] ?? null, 'is_active' => request()->boolean('is_active', true)];
    }

    private function rejectDuplicate(array $payload, int $tenantId, ?int $ignoreId = null): void
    {
        $query = AssetModel::query()->forTenant($tenantId)->where('asset_brand_id', $payload['asset_brand_id'])->where('asset_type_id', $payload['asset_type_id'])->where('name_normalized', $payload['name_normalized']);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        abort_if($query->exists(), 422, 'Modelo duplicado para marca e tipo.');
    }

    private function toggle(AssetModel $model, CurrentTenant $currentTenant, AuditLogger $audit, bool $active, string $action, string $message): RedirectResponse
    {
        $this->guardTenant($model, $currentTenant);
        $this->authorize('deactivate', $model);
        DB::transaction(fn () => $model->update(['is_active' => $active]));
        $audit->record($action, $model);
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return back()->with('success', $message);
    }

    private function guardTenant(AssetModel $model, CurrentTenant $currentTenant): void
    {
        abort_unless($model->tenant_id === $currentTenant->id(), 404);
    }

    private function options(CurrentTenant $currentTenant): array
    {
        return ['brands' => AssetBrand::query()->forTenant($currentTenant->id())->orderBy('name')->get(['id', 'public_id', 'name']), 'types' => AssetType::query()->forTenant($currentTenant->id())->orderBy('name')->get(['id', 'public_id', 'name'])];
    }
}
