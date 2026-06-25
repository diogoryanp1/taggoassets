<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreAssetConditionRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AssetConditionController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', AssetCondition::class);
        $query = AssetCondition::query()->availableToTenant($currentTenant->id())->orderBy('sort_order')->orderBy('name');
        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%'.$request->string('search').'%');
        }
        if ($request->filled('origin')) {
            $request->string('origin')->toString() === 'system' ? $query->whereNull('tenant_id') : $query->where('tenant_id', $currentTenant->id());
        }

        return view('catalog.conditions.index', ['conditions' => $query->paginate($pagination->resolve($request))->withQueryString()]);
    }

    public function create(): View
    {
        $this->authorize('create', AssetCondition::class);

        return view('catalog.conditions.create');
    }

    public function store(StoreAssetConditionRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', AssetCondition::class);
        $tenant = $currentTenant->require();
        $payload = $this->payload($request->validated(), $tenant->id);
        abort_if(AssetCondition::query()->where('tenant_id', $tenant->id)->where('code', $payload['code'])->exists(), 422, 'Condicao duplicada.');
        $condition = DB::transaction(fn () => AssetCondition::forceCreate($payload));
        $audit->record('asset_condition.created', $condition, [], $condition->only(['name', 'code', 'sort_order', 'is_active']));
        Cache::forget("tenant:{$tenant->id}:dashboard:summary");

        return redirect()->route('catalog.conditions.show', $condition)->with('success', 'Condicao cadastrada com sucesso.');
    }

    public function show(AssetCondition $condition, CurrentTenant $currentTenant): View
    {
        $this->guardAvailable($condition, $currentTenant);
        $this->authorize('viewAny', AssetCondition::class);

        return view('catalog.conditions.show', compact('condition'));
    }

    public function edit(AssetCondition $condition, CurrentTenant $currentTenant): View
    {
        $this->guardAvailable($condition, $currentTenant);
        $this->authorize('update', $condition);

        return view('catalog.conditions.edit', compact('condition'));
    }

    public function update(StoreAssetConditionRequest $request, AssetCondition $condition, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->guardAvailable($condition, $currentTenant);
        $this->authorize('update', $condition);
        $payload = $this->payload($request->validated(), $currentTenant->id());
        abort_if(AssetCondition::query()->where('tenant_id', $currentTenant->id())->where('code', $payload['code'])->whereKeyNot($condition->id)->exists(), 422, 'Condicao duplicada.');
        $old = $condition->only(['name', 'code', 'sort_order', 'is_active']);
        DB::transaction(fn () => $condition->update($payload));
        $audit->record('asset_condition.updated', $condition, $old, $condition->only(array_keys($old)));
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return redirect()->route('catalog.conditions.show', $condition)->with('success', 'Condicao atualizada com sucesso.');
    }

    public function deactivate(AssetCondition $condition, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($condition, $currentTenant, $audit, false, 'asset_condition.deactivated', 'Condicao inativada com sucesso.');
    }

    public function reactivate(AssetCondition $condition, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($condition, $currentTenant, $audit, true, 'asset_condition.reactivated', 'Condicao reativada com sucesso.');
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function payload(array $data, int $tenantId): array
    {
        $name = Str::squish((string) $data['name']);
        $code = filled($data['code'] ?? null) ? Str::slug((string) $data['code'], '_') : Str::slug($name, '_');

        return ['tenant_id' => $tenantId, 'name' => $name, 'code' => $code, 'description' => $data['description'] ?? null, 'sort_order' => (int) ($data['sort_order'] ?? 0), 'is_system' => false, 'is_active' => request()->boolean('is_active', true)];
    }

    private function toggle(AssetCondition $condition, CurrentTenant $currentTenant, AuditLogger $audit, bool $active, string $action, string $message): RedirectResponse
    {
        $this->guardAvailable($condition, $currentTenant);
        $this->authorize('deactivate', $condition);
        DB::transaction(fn () => $condition->update(['is_active' => $active]));
        $audit->record($action, $condition);
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return back()->with('success', $message);
    }

    private function guardAvailable(AssetCondition $condition, CurrentTenant $currentTenant): void
    {
        abort_unless($condition->tenant_id === null || $condition->tenant_id === $currentTenant->id(), 404);
    }
}
