<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\UpdateOrganizationalUnitRequest;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationalUnitController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', OrganizationalUnit::class);
        $tenant = $currentTenant->require();
        $query = OrganizationalUnit::forTenant($tenant->id)->with('parent:id,name')->orderBy('name');
        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%'.$request->string('search').'%');
        }

        return view('units.index', ['units' => $query->paginate($pagination->resolve($request))->withQueryString()]);
    }

    public function create(CurrentTenant $currentTenant): View
    {
        $this->authorize('create', OrganizationalUnit::class);

        return view('units.create', ['parents' => OrganizationalUnit::forTenant($currentTenant->id())->orderBy('name')->get(['public_id', 'name'])]);
    }

    public function store(UpdateOrganizationalUnitRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', OrganizationalUnit::class);
        $data = $request->validated();
        $tenant = $currentTenant->require();
        $data['parent_id'] = ! empty($data['parent_id']) ? OrganizationalUnit::forTenant($tenant->id)->where('public_id', $data['parent_id'])->value('id') : null;
        $unit = $tenant->units()->create($data);
        $audit->record('organizational_unit.created', $unit, [], $unit->only(['name', 'code', 'type', 'status']));

        return redirect()->route('units.index')->with('success', 'Unidade cadastrada.');
    }

    public function edit(OrganizationalUnit $unit, CurrentTenant $currentTenant): View
    {
        abort_unless($unit->tenant_id === $currentTenant->id(), 404);
        $this->authorize('update', $unit);

        return view('units.edit', ['unit' => $unit, 'parents' => OrganizationalUnit::forTenant($currentTenant->id())->whereKeyNot($unit->id)->orderBy('name')->get(['public_id', 'name'])]);
    }

    public function update(UpdateOrganizationalUnitRequest $request, OrganizationalUnit $unit, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        abort_unless($unit->tenant_id === $currentTenant->id(), 404);
        $this->authorize('update', $unit);
        $data = $request->validated();
        $data['parent_id'] = ! empty($data['parent_id']) ? OrganizationalUnit::forTenant($currentTenant->id())->where('public_id', $data['parent_id'])->value('id') : null;
        abort_if($data['parent_id'] === $unit->id, 422);
        $old = $unit->only(['name', 'code', 'type', 'status', 'parent_id']);
        $unit->update($data);
        $audit->record('organizational_unit.updated', $unit, $old, $unit->only(['name', 'code', 'type', 'status', 'parent_id']));

        return redirect()->route('units.index')->with('success', 'Unidade atualizada.');
    }
}
