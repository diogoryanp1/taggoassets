<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\UnitOfMeasure;
use App\Domain\Audit\AuditLogger;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreUnitOfMeasureRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UnitOfMeasureController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', UnitOfMeasure::class);
        $query = UnitOfMeasure::query()->availableToTenant($currentTenant->id())->orderBy('is_system', 'desc')->orderBy('name');
        if ($request->filled('search')) {
            $query->where(fn ($query) => $query->where('name', 'ilike', '%'.$request->string('search').'%')->orWhere('symbol', 'ilike', '%'.$request->string('search').'%'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('origin')) {
            $request->string('origin')->toString() === 'system' ? $query->whereNull('tenant_id') : $query->where('tenant_id', $currentTenant->id());
        }

        return view('catalog.units.index', ['units' => $query->paginate($pagination->resolve($request))->withQueryString()]);
    }

    public function create(): View
    {
        $this->authorize('create', UnitOfMeasure::class);

        return view('catalog.units.create');
    }

    public function store(StoreUnitOfMeasureRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', UnitOfMeasure::class);
        $tenant = $currentTenant->require();
        $payload = $this->payload($request->validated(), $tenant->id);
        $this->rejectDuplicate($payload, $tenant->id);
        $unit = DB::transaction(fn () => UnitOfMeasure::forceCreate($payload));
        $audit->record('unit_of_measure.created', $unit, [], $unit->only(['name', 'symbol', 'type', 'decimal_places', 'is_active']));
        Cache::forget("tenant:{$tenant->id}:dashboard:summary");

        return redirect()->route('catalog.units.show', $unit)->with('success', 'Unidade de medida cadastrada com sucesso.');
    }

    public function show(UnitOfMeasure $unit, CurrentTenant $currentTenant): View
    {
        $this->guardAvailable($unit, $currentTenant);
        $this->authorize('viewAny', UnitOfMeasure::class);

        return view('catalog.units.show', compact('unit'));
    }

    public function edit(UnitOfMeasure $unit, CurrentTenant $currentTenant): View
    {
        $this->guardAvailable($unit, $currentTenant);
        $this->authorize('update', $unit);

        return view('catalog.units.edit', compact('unit'));
    }

    public function update(StoreUnitOfMeasureRequest $request, UnitOfMeasure $unit, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->guardAvailable($unit, $currentTenant);
        $this->authorize('update', $unit);
        $payload = $this->payload($request->validated(), $currentTenant->id());
        $this->rejectDuplicate($payload, $currentTenant->id(), $unit->id);
        $old = $unit->only(['name', 'symbol', 'type', 'decimal_places', 'is_active']);
        DB::transaction(fn () => $unit->update($payload));
        $audit->record('unit_of_measure.updated', $unit, $old, $unit->only(array_keys($old)));
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return redirect()->route('catalog.units.show', $unit)->with('success', 'Unidade de medida atualizada com sucesso.');
    }

    public function deactivate(UnitOfMeasure $unit, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($unit, $currentTenant, $audit, false, 'unit_of_measure.deactivated', 'Unidade de medida inativada com sucesso.');
    }

    public function reactivate(UnitOfMeasure $unit, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        return $this->toggle($unit, $currentTenant, $audit, true, 'unit_of_measure.reactivated', 'Unidade de medida reativada com sucesso.');
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function payload(array $data, int $tenantId): array
    {
        $name = Str::squish((string) $data['name']);
        $symbol = Str::upper(Str::squish((string) $data['symbol']));

        return ['tenant_id' => $tenantId, 'name' => $name, 'name_normalized' => Str::lower($name), 'symbol' => $symbol, 'symbol_normalized' => Str::lower($symbol), 'type' => $data['type'], 'decimal_places' => (int) $data['decimal_places'], 'is_system' => false, 'is_active' => request()->boolean('is_active', true)];
    }

    private function rejectDuplicate(array $payload, int $tenantId, ?int $ignoreId = null): void
    {
        foreach (['name_normalized', 'symbol_normalized'] as $column) {
            $query = UnitOfMeasure::query()->where('tenant_id', $tenantId)->where($column, $payload[$column]);
            if ($ignoreId !== null) {
                $query->whereKeyNot($ignoreId);
            }
            abort_if($query->exists(), 422, 'Unidade de medida duplicada.');
        }
    }

    private function toggle(UnitOfMeasure $unit, CurrentTenant $currentTenant, AuditLogger $audit, bool $active, string $action, string $message): RedirectResponse
    {
        $this->guardAvailable($unit, $currentTenant);
        $this->authorize('deactivate', $unit);
        DB::transaction(fn () => $unit->update(['is_active' => $active]));
        $audit->record($action, $unit);
        Cache::forget("tenant:{$currentTenant->id()}:dashboard:summary");

        return back()->with('success', $message);
    }

    private function guardAvailable(UnitOfMeasure $unit, CurrentTenant $currentTenant): void
    {
        abort_unless($unit->tenant_id === null || $unit->tenant_id === $currentTenant->id(), 404);
    }
}
