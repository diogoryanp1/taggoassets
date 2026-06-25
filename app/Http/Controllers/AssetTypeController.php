<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreAssetTypeRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetTypeController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', AssetType::class);
        $query = AssetType::query()->forTenant($currentTenant->id())->with(['category:id,name'])->withCount('category')->orderBy('name');
        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%'.$request->string('search').'%');
        }
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($query) => $query->where('public_id', $request->string('category')));
        }
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return view('catalog.types.index', ['types' => $query->paginate($pagination->resolve($request))->withQueryString(), 'categories' => $this->categories($currentTenant)]);
    }

    public function create(CurrentTenant $currentTenant): View
    {
        $this->authorize('create', AssetType::class);

        return view('catalog.types.create', ['categories' => $this->categories($currentTenant)]);
    }

    public function store(StoreAssetTypeRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', AssetType::class);
        $tenant = $currentTenant->require();
        $data = $request->validated();
        $category = AssetCategory::query()->forTenant($tenant->id)->where('public_id', $data['asset_category_id'])->where('is_active', true)->firstOrFail();
        $payload = $this->payload($data, $request, $tenant->id, $category->id);
        abort_if(AssetType::query()->forTenant($tenant->id)->where('asset_category_id', $category->id)->where('name_normalized', $payload['name_normalized'])->exists(), 422, 'Tipo duplicado na categoria.');
        abort_if($payload['code'] !== null && AssetType::query()->forTenant($tenant->id)->where('code', $payload['code'])->exists(), 422, 'Codigo duplicado.');
        $type = DB::transaction(fn () => AssetType::forceCreate($payload + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]));
        $audit->record('asset_type.created', $type, [], $type->only(['name', 'code', 'is_active', 'requires_serial_number', 'requires_brand', 'requires_model', 'is_depreciable', 'default_useful_life_months']));
        Cache::forget("tenant:{$tenant->id}:dashboard:summary");

        return redirect()->route('catalog.types.show', $type)->with('success', 'Tipo cadastrado com sucesso.');
    }

    public function show(AssetType $type, CurrentTenant $currentTenant): View
    {
        $this->guardTenant($type, $currentTenant);
        $this->authorize('viewAny', AssetType::class);
        $type->load('category:id,name');

        return view('catalog.types.show', compact('type'));
    }

    public function edit(AssetType $type, CurrentTenant $currentTenant): View
    {
        $this->guardTenant($type, $currentTenant);
        $this->authorize('update', $type);

        return view('catalog.types.edit', ['type' => $type, 'categories' => $this->categories($currentTenant)]);
    }

    public function update(StoreAssetTypeRequest $request, AssetType $type, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->guardTenant($type, $currentTenant);
        $this->authorize('update', $type);
        $tenant = $currentTenant->require();
        $data = $request->validated();
        $category = AssetCategory::query()->forTenant($tenant->id)->where('public_id', $data['asset_category_id'])->firstOrFail();
        $payload = $this->payload($data, $request, $tenant->id, $category->id);
        abort_if(AssetType::query()->forTenant($tenant->id)->where('asset_category_id', $category->id)->where('name_normalized', $payload['name_normalized'])->whereKeyNot($type->id)->exists(), 422, 'Tipo duplicado na categoria.');
        abort_if($payload['code'] !== null && AssetType::query()->forTenant($tenant->id)->where('code', $payload['code'])->whereKeyNot($type->id)->exists(), 422, 'Codigo duplicado.');
        $old = $type->only(['name', 'code', 'asset_category_id', 'is_active', 'requires_serial_number', 'requires_brand', 'requires_model', 'is_depreciable', 'default_useful_life_months']);
        DB::transaction(fn () => $type->update($payload + ['updated_by' => $request->user()->id]));
        $audit->record('asset_type.updated', $type, $old, $type->only(array_keys($old)));
        Cache::forget("tenant:{$tenant->id}:dashboard:summary");

        return redirect()->route('catalog.types.show', $type)->with('success', 'Tipo atualizado com sucesso.');
    }

    public function deactivate(AssetType $type, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($type, $currentTenant, $audit, false, 'asset_type.deactivated', 'Tipo inativado com sucesso.');
    }

    public function reactivate(AssetType $type, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($type, $currentTenant, $audit, true, 'asset_type.reactivated', 'Tipo reativado com sucesso.');
    }

    private function toggle(AssetType $type, CurrentTenant $currentTenant, AuditLogger $audit, bool $active, string $action, string $message): RedirectResponse
    {
        $this->guardTenant($type, $currentTenant);
        $this->authorize('deactivate', $type);
        DB::transaction(fn () => $type->update(['is_active' => $active]));
        $audit->record($action, $type);
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return back()->with('success', $message);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function payload(array $data, Request $request, int $tenantId, int $categoryId): array
    {
        $isDepreciable = $request->boolean('is_depreciable');
        abort_if(! $isDepreciable && filled($data['default_useful_life_months'] ?? null), 422, 'Vida util so pode ser informada para tipos depreciaveis.');

        return ['tenant_id' => $tenantId, 'asset_category_id' => $categoryId, 'name' => Str::squish((string) $data['name']), 'name_normalized' => Str::lower(Str::squish((string) $data['name'])), 'code' => filled($data['code'] ?? null) ? Str::upper(Str::squish((string) $data['code'])) : null, 'description' => $data['description'] ?? null, 'is_active' => $request->boolean('is_active', true), 'requires_serial_number' => $request->boolean('requires_serial_number'), 'requires_brand' => $request->boolean('requires_brand'), 'requires_model' => $request->boolean('requires_model'), 'is_depreciable' => $isDepreciable, 'default_useful_life_months' => $isDepreciable ? ($data['default_useful_life_months'] ?? null) : null];
    }

    private function guardTenant(AssetType $type, CurrentTenant $currentTenant): void
    {
        abort_unless($type->tenant_id === $currentTenant->id(), 404);
    }

    private function categories(CurrentTenant $currentTenant)
    {
        return AssetCategory::query()->forTenant($currentTenant->id())->orderBy('name')->get(['id', 'public_id', 'name']);
    }
}
