<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreAssetBrandRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetBrandController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', AssetBrand::class);
        $query = AssetBrand::query()->forTenant($currentTenant->id())->withCount('models')->orderBy('name');
        $this->applyCommonFilters($query, $request);

        return view('catalog.brands.index', ['brands' => $query->paginate($pagination->resolve($request))->withQueryString()]);
    }

    public function create(): View
    {
        $this->authorize('create', AssetBrand::class);

        return view('catalog.brands.create');
    }

    public function store(StoreAssetBrandRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', AssetBrand::class);
        $tenant = $currentTenant->require();
        $name = Str::squish($request->string('name')->toString());
        $normalized = Str::lower($name);
        abort_if(AssetBrand::query()->forTenant($tenant->id)->where('name_normalized', $normalized)->exists(), 422, 'Marca duplicada.');
        $brand = DB::transaction(fn () => AssetBrand::forceCreate(['tenant_id' => $tenant->id, 'name' => $name, 'name_normalized' => $normalized, 'is_active' => $request->boolean('is_active', true)]));
        $audit->record('asset_brand.created', $brand, [], $brand->only(['name', 'is_active']));
        Cache::forget("tenant:{$tenant->id}:dashboard:summary");

        return redirect()->route('catalog.brands.show', $brand)->with('success', 'Marca cadastrada com sucesso.');
    }

    public function show(AssetBrand $brand, CurrentTenant $currentTenant): View
    {
        $this->guardTenant($brand, $currentTenant);
        $this->authorize('viewAny', AssetBrand::class);
        $brand->loadCount(['models']);

        return view('catalog.brands.show', compact('brand'));
    }

    public function edit(AssetBrand $brand, CurrentTenant $currentTenant): View
    {
        $this->guardTenant($brand, $currentTenant);
        $this->authorize('update', $brand);

        return view('catalog.brands.edit', compact('brand'));
    }

    public function update(StoreAssetBrandRequest $request, AssetBrand $brand, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->guardTenant($brand, $currentTenant);
        $this->authorize('update', $brand);
        $name = Str::squish($request->string('name')->toString());
        $normalized = Str::lower($name);
        abort_if(AssetBrand::query()->forTenant($currentTenant->id())->where('name_normalized', $normalized)->whereKeyNot($brand->id)->exists(), 422, 'Marca duplicada.');
        $old = $brand->only(['name', 'is_active']);
        DB::transaction(fn () => $brand->update(['name' => $name, 'name_normalized' => $normalized, 'is_active' => $request->boolean('is_active', true)]));
        $audit->record('asset_brand.updated', $brand, $old, $brand->only(['name', 'is_active']));
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return redirect()->route('catalog.brands.show', $brand)->with('success', 'Marca atualizada com sucesso.');
    }

    public function deactivate(AssetBrand $brand, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($brand, $currentTenant, $audit, false, 'asset_brand.deactivated', 'Marca inativada com sucesso.');
    }

    public function reactivate(AssetBrand $brand, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($brand, $currentTenant, $audit, true, 'asset_brand.reactivated', 'Marca reativada com sucesso.');
    }

    private function toggle(AssetBrand $brand, CurrentTenant $currentTenant, AuditLogger $audit, bool $active, string $action, string $message): RedirectResponse
    {
        $this->guardTenant($brand, $currentTenant);
        $this->authorize('deactivate', $brand);
        DB::transaction(fn () => $brand->update(['is_active' => $active]));
        $audit->record($action, $brand);
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return back()->with('success', $message);
    }

    private function guardTenant(AssetBrand $brand, CurrentTenant $currentTenant): void
    {
        abort_unless($brand->tenant_id === $currentTenant->id(), 404);
    }

    private function applyCommonFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%'.$request->string('search').'%');
        }
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }
    }
}
