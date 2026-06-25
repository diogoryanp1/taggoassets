<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Services\AssetCategoryHierarchy;
use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreAssetCategoryRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetCategoryController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', AssetCategory::class);
        $query = AssetCategory::query()->forTenant($currentTenant->id())->whereNull('parent_id')->withCount('children')->orderBy('sort_order')->orderBy('name');
        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%'.$request->string('search').'%');
        }

        return view('catalog.categories.index', ['categories' => $query->paginate($pagination->resolve($request))->withQueryString()]);
    }

    public function children(AssetCategory $category, CurrentTenant $currentTenant): JsonResponse
    {
        abort_unless($category->tenant_id === $currentTenant->id(), 404);
        $this->authorize('viewAny', AssetCategory::class);

        return response()->json($category->children()->withCount('children')->orderBy('sort_order')->orderBy('name')->get(['id', 'public_id', 'name', 'code', 'is_active', 'sort_order']));
    }

    public function create(): View
    {
        $this->authorize('create', AssetCategory::class);

        return view('catalog.categories.create');
    }

    public function store(StoreAssetCategoryRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', AssetCategory::class);
        $data = $request->validated();
        $tenant = $currentTenant->require();
        $parent = ! empty($data['parent_id']) ? AssetCategory::query()->forTenant($tenant->id)->where('public_id', $data['parent_id'])->firstOrFail() : null;
        $category = DB::transaction(function () use ($data, $tenant, $request, $parent): AssetCategory {
            return AssetCategory::forceCreate(['tenant_id' => $tenant->id, 'parent_id' => $parent?->id, 'name' => trim($data['name']), 'name_normalized' => Str::lower(trim($data['name'])), 'code' => filled($data['code'] ?? null) ? Str::upper(trim($data['code'])) : null, 'description' => $data['description'] ?? null, 'is_active' => $request->boolean('is_active', true), 'sort_order' => $data['sort_order'] ?? 0, 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        });
        $audit->record('asset_category.created', $category, [], $category->only(['name', 'code', 'is_active']));
        Cache::forget("tenant:{$tenant->id}:dashboard:summary");

        return redirect()->route('catalog.categories.index')->with('success', 'Categoria cadastrada com sucesso.');
    }

    public function edit(AssetCategory $category, CurrentTenant $currentTenant): View
    {
        abort_unless($category->tenant_id === $currentTenant->id(), 404);
        $this->authorize('update', $category);

        return view('catalog.categories.edit', compact('category'));
    }

    public function update(StoreAssetCategoryRequest $request, AssetCategory $category, CurrentTenant $currentTenant, AssetCategoryHierarchy $hierarchy, AuditLogger $audit): RedirectResponse
    {
        abort_unless($category->tenant_id === $currentTenant->id(), 404);
        $this->authorize('update', $category);
        $data = $request->validated();
        $parent = ! empty($data['parent_id']) ? AssetCategory::query()->forTenant($currentTenant->id())->where('public_id', $data['parent_id'])->firstOrFail() : null;
        abort_unless($hierarchy->acceptsParent($category, $parent), 422);
        $old = $category->only(['name', 'code', 'parent_id', 'is_active']);
        DB::transaction(fn () => $category->update(['parent_id' => $parent?->id, 'name' => trim($data['name']), 'name_normalized' => Str::lower(trim($data['name'])), 'code' => filled($data['code'] ?? null) ? Str::upper(trim($data['code'])) : null, 'description' => $data['description'] ?? null, 'is_active' => $request->boolean('is_active', true), 'sort_order' => $data['sort_order'] ?? 0, 'updated_by' => $request->user()->id]));
        $audit->record('asset_category.updated', $category, $old, $category->only(['name', 'code', 'parent_id', 'is_active']));
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return redirect()->route('catalog.categories.index')->with('success', 'Categoria atualizada com sucesso.');
    }

    public function deactivate(AssetCategory $category, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        abort_unless($category->tenant_id === $currentTenant->id(), 404);
        $this->authorize('deactivate', $category);
        $category->update(['is_active' => false]);
        $audit->record('asset_category.deactivated', $category);
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return back()->with('success', 'Categoria inativada com sucesso.');
    }
}
