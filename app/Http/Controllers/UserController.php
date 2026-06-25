<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\SessionManager;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\CurrentTenant;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        $this->authorize('viewAny', User::class);
        $tenant = $currentTenant->require();
        $query = $tenant->users()->select('users.id', 'users.public_id', 'users.name', 'users.email', 'users.status', 'users.last_login_at')->withPivot('role_id', 'status')->with('organizationalUnits:id,name');
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('users.name', 'ilike', "%{$search}%")->orWhere('users.email', 'ilike', "%{$search}%"));
        }
        if ($request->filled('status')) {
            $query->where('users.status', $request->string('status'));
        }
        if ($request->filled('role')) {
            $query->wherePivot('role_id', Role::where('name', $request->string('role'))->value('id'));
        }

        return view('users.index', ['users' => $query->paginate($pagination->resolve($request))->withQueryString(), 'roles' => Role::where('name', '!=', 'super_admin')->orderBy('label')->get(['id', 'name', 'label'])]);
    }

    public function create(CurrentTenant $currentTenant): View
    {
        $this->authorize('create', User::class);

        return view('users.create', ['roles' => Role::where('name', '!=', 'super_admin')->orderBy('label')->get(), 'units' => OrganizationalUnit::forTenant($currentTenant->id())->orderBy('name')->get(['id', 'public_id', 'name'])]);
    }

    public function store(StoreUserRequest $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('create', User::class);
        $data = $request->validated();
        $tenant = $currentTenant->require();
        DB::transaction(function () use ($data, $tenant, $audit): void {
            $user = User::create(collect($data)->only(['name', 'email', 'password'])->all());
            $role = Role::where('name', $data['role'])->firstOrFail();
            $user->tenants()->attach($tenant->id, ['role_id' => $role->id, 'status' => 'active']);
            $units = OrganizationalUnit::forTenant($tenant->id)->whereIn('public_id', $data['organizational_units'] ?? [])->pluck('id');
            $user->organizationalUnits()->sync($units);
            $audit->record('user.created', $user, [], ['name' => $user->name, 'email' => $user->email, 'role' => $role->name]);
        });

        return redirect()->route('users.index')->with('success', 'Usuário cadastrado.');
    }

    public function block(User $user, CurrentTenant $currentTenant, AuditLogger $audit, SessionManager $sessions): RedirectResponse
    {
        abort_unless($user->tenants()->whereKey($currentTenant->id())->exists(), 404);
        $this->authorize('block', $user);
        $user->forceFill(['status' => 'blocked', 'blocked_at' => now()])->save();
        $sessions->revokeAll($user);
        $audit->record('user.blocked', $user);

        return back()->with('success', 'Usuário bloqueado.');
    }
}
