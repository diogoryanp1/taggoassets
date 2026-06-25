<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\LocationHierarchy;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreLocationRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', Location::class);
        $query = Location::forTenant($currentTenant->id())->whereNull('parent_id')->select(['id', 'public_id', 'organizational_unit_id', 'type', 'code', 'name', 'status'])->with('unit:id,name');
        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%'.$request->string('search').'%');
        }

        return view('locations.index', ['locations' => $query->paginate($pagination->resolve($request))->withQueryString()]);
    }

    public function children(Location $location, CurrentTenant $currentTenant): JsonResponse
    {
        abort_unless($location->tenant_id === $currentTenant->id(), 404);
        $this->authorize('view', $location);

        return response()->json(Location::forTenant($currentTenant->id())->where('parent_id', $location->id)->orderBy('name')->get(['public_id', 'type', 'code', 'name', 'status']));
    }

    public function create(CurrentTenant $currentTenant): View
    {
        $this->authorize('create', Location::class);

        return view('locations.create', ['units' => OrganizationalUnit::forTenant($currentTenant->id())->orderBy('name')->get(['public_id', 'name']), 'parents' => []]);
    }

    public function edit(Location $location, CurrentTenant $currentTenant): View
    {
        abort_unless($location->tenant_id === $currentTenant->id(), 404);
        $this->authorize('update', $location);

        return view('locations.edit', ['location' => $location, 'units' => OrganizationalUnit::forTenant($currentTenant->id())->orderBy('name')->get(['public_id', 'name'])]);
    }

    public function store(StoreLocationRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', Location::class);
        $data = $request->validated();
        $tenantId = $currentTenant->id();
        $unitId = OrganizationalUnit::forTenant($tenantId)->where('public_id', $data['organizational_unit_id'])->value('id');
        abort_unless($unitId, 404);
        $parent = ! empty($data['parent_id']) ? Location::forTenant($tenantId)->where('public_id', $data['parent_id'])->firstOrFail() : null;
        abort_if($parent?->organizational_unit_id !== null && $parent->organizational_unit_id !== $unitId, 422);
        $location = \DB::transaction(fn () => Location::forceCreate([...$data, 'organizational_unit_id' => $unitId, 'parent_id' => $parent?->id, 'tenant_id' => $tenantId]));
        $audit->record('location.created', $location, [], $location->only(['name', 'type', 'status']));

        return redirect()->route('locations.index')->with('success', 'Localização cadastrada.');
    }

    public function update(StoreLocationRequest $request, Location $location, CurrentTenant $currentTenant, AuditLogger $audit, LocationHierarchy $hierarchy): RedirectResponse
    {
        abort_unless($location->tenant_id === $currentTenant->id(), 404);
        $this->authorize('update', $location);
        $data = $request->validated();
        $tenantId = $currentTenant->id();
        $unitId = OrganizationalUnit::forTenant($tenantId)->where('public_id', $data['organizational_unit_id'])->value('id');
        abort_unless($unitId, 404);
        $parent = ! empty($data['parent_id']) ? Location::forTenant($tenantId)->where('public_id', $data['parent_id'])->firstOrFail() : null;
        abort_unless($hierarchy->acceptsParent($location, $parent, $unitId), 422);
        $old = $location->only(['name', 'type', 'status', 'parent_id']);
        \DB::transaction(fn () => $location->update([...$data, 'organizational_unit_id' => $unitId, 'parent_id' => $parent?->id]));
        $audit->record('location.updated', $location, $old, $location->only(['name', 'type', 'status', 'parent_id']));

        return redirect()->route('locations.index')->with('success', 'Localização atualizada.');
    }
}
