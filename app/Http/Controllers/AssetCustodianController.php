<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\AssetCustodian;
use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreAssetCustodianRequest;
use App\Models\User;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetCustodianController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', AssetCustodian::class);
        $query = AssetCustodian::query()->forTenant($currentTenant->id())->with(['organizationalUnit:id,name', 'user:id,name,email'])->withCount('assets');
        if ($request->filled('name')) {
            $query->where('name', 'ilike', '%'.$request->string('name').'%');
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return view('custodians.index', ['custodians' => $query->orderBy('name')->paginate(min($pagination->resolve($request), 100))->withQueryString()]);
    }

    public function create(CurrentTenant $currentTenant): View
    {
        $this->authorize('create', AssetCustodian::class);

        return view('custodians.create', $this->options($currentTenant));
    }

    public function store(StoreAssetCustodianRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', AssetCustodian::class);
        $tenant = $currentTenant->require();
        $unit = OrganizationalUnit::query()->forTenant($tenant->id)->where('public_id', $request->string('organizational_unit'))->firstOrFail();
        $userId = null;
        if ($request->filled('user')) {
            $user = User::query()->where('public_id', $request->string('user'))->whereHas('tenants', fn ($query) => $query->whereKey($tenant->id)->where('tenant_user.status', 'active'))->firstOrFail();
            $userId = $user->id;
        }
        $custodian = DB::transaction(fn () => AssetCustodian::forceCreate([
            'tenant_id' => $tenant->id,
            'organizational_unit_id' => $unit->id,
            'user_id' => $userId,
            'name' => $request->string('name'),
            'registration_number' => $request->input('registration_number'),
            'document_identifier' => $request->input('document_identifier'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'position' => $request->input('position'),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
            'is_active' => true,
        ]));
        $audit->record('asset_custodian.created', $custodian, [], $custodian->only(['name', 'registration_number', 'organizational_unit_id']));

        return redirect()->route('custodians.index')->with('success', 'Responsável cadastrado com sucesso.');
    }

    public function edit(AssetCustodian $custodian, CurrentTenant $currentTenant): View
    {
        abort_unless($custodian->tenant_id === $currentTenant->id(), 404);
        $this->authorize('update', $custodian);

        return view('custodians.edit', ['custodian' => $custodian, ...$this->options($currentTenant)]);
    }

    public function update(StoreAssetCustodianRequest $request, AssetCustodian $custodian, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        abort_unless($custodian->tenant_id === $currentTenant->id(), 404);
        $this->authorize('update', $custodian);
        $old = $custodian->only(['name', 'registration_number', 'organizational_unit_id', 'is_active']);
        $unit = OrganizationalUnit::query()->forTenant($currentTenant->id())->where('public_id', $request->string('organizational_unit'))->firstOrFail();
        $custodian->update($request->safe()->except(['organizational_unit', 'user']) + ['organizational_unit_id' => $unit->id, 'updated_by' => $request->user()->id]);
        $audit->record('asset_custodian.updated', $custodian, $old, $custodian->only(['name', 'registration_number', 'organizational_unit_id', 'is_active']));

        return redirect()->route('custodians.index')->with('success', 'Responsável atualizado com sucesso.');
    }

    public function deactivate(AssetCustodian $custodian, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        abort_unless($custodian->tenant_id === $currentTenant->id(), 404);
        $this->authorize('deactivate', $custodian);
        $old = $custodian->only(['is_active']);
        $custodian->update(['is_active' => false]);
        $audit->record('asset_custodian.deactivated', $custodian, $old, ['is_active' => false]);

        return back()->with('success', 'Responsável inativado com sucesso.');
    }

    private function options(CurrentTenant $currentTenant): array
    {
        $tenant = $currentTenant->require();

        return [
            'organizationalUnits' => OrganizationalUnit::query()->forTenant($tenant->id)->where('status', 'active')->orderBy('name')->get(['id', 'public_id', 'name']),
            'users' => User::query()->whereHas('tenants', fn ($query) => $query->whereKey($tenant->id)->where('tenant_user.status', 'active'))->orderBy('name')->get(['id', 'public_id', 'name', 'email']),
        ];
    }
}
