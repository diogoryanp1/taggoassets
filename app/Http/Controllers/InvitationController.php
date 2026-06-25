<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\UserInvitation;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function index(Request $request, CurrentTenant $currentTenant, PaginationResolver $pagination): View
    {
        abort_unless($request->user()->hasPermission($currentTenant->require(), 'users.view'), 403);

        return view('invitations.index', ['invitations' => UserInvitation::where('tenant_id', $currentTenant->id())->latest()->paginate($pagination->resolve($request))]);
    }

    public function create(Request $request, CurrentTenant $currentTenant): View
    {
        abort_unless($request->user()->hasPermission($currentTenant->require(), 'users.create'), 403);

        return view('invitations.create', ['roles' => Role::whereNotIn('name', ['super_admin'])->get()]);
    }

    public function store(Request $request, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->hasPermission($currentTenant->require(), 'users.create'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255'], 'role' => ['required', 'exists:roles,name', 'not_in:super_admin']]);
        $email = Str::lower($data['email']);
        $tenant = $currentTenant->require();
        $token = Str::random(64);
        $invitation = DB::transaction(function () use ($data, $email, $tenant, $token, $request, $audit): UserInvitation {
            $user = User::firstOrCreate(['email' => $email], ['name' => $data['name'], 'password' => Hash::make(Str::random(64))]);
            if ($user->wasRecentlyCreated) {
                $user->forceFill(['status' => 'invited'])->save();
            }
            abort_if($user->status === 'blocked', 422, 'Não é possível convidar uma conta bloqueada.');
            $role = Role::where('name', $data['role'])->firstOrFail();
            $user->tenants()->syncWithoutDetaching([$tenant->id => ['role_id' => $role->id, 'status' => 'inactive']]);
            UserInvitation::where('tenant_id', $tenant->id)->where('email', $email)->whereNull('accepted_at')->update(['revoked_at' => now()]);
            $invitation = UserInvitation::create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role_id' => $role->id, 'invited_by' => $request->user()->id, 'email' => $email, 'name' => $data['name'], 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(3)]);
            $audit->record('invitation.created', $user, [], ['email' => $email, 'role' => $role->name]);

            return $invitation;
        });
        Notification::route('mail', $email)->notify(new UserInvitationNotification($invitation, $token));

        return redirect()->route('invitations.index')->with('success', 'Convite criado. Em ambiente local, use o mailer de log para obter o link.');
    }

    public function accept(Request $request, UserInvitation $invitation): View|RedirectResponse
    {
        abort_unless($invitation->isUsable() && hash_equals($invitation->token_hash, hash('sha256', (string) $request->query('token'))), 404);

        return view('invitations.accept', compact('invitation'));
    }

    public function complete(Request $request, UserInvitation $invitation, AuditLogger $audit, CurrentTenant $currentTenant): RedirectResponse
    {
        abort_unless($invitation->isUsable() && hash_equals($invitation->token_hash, hash('sha256', (string) $request->input('token'))), 404);
        $data = $request->validate(['password' => ['required', 'confirmed', 'min:12']]);
        abort_if($invitation->user->status === 'blocked', 404);
        $invitation->user->forceFill(['password' => Hash::make($data['password']), 'status' => 'active', 'email_verified_at' => now()])->save();
        $invitation->user->tenants()->updateExistingPivot($invitation->tenant_id, ['status' => 'active']);
        $invitation->update(['accepted_at' => now()]);
        $currentTenant->set($invitation->tenant);
        $audit->record('invitation.accepted', $invitation->user);

        return redirect()->route('login')->with('status', 'Conta ativada.');
    }

    public function resend(Request $request, UserInvitation $invitation, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        $tenant = $currentTenant->require();
        abort_unless($invitation->tenant_id === $tenant->id, 404);
        abort_unless($request->user()->status === 'active' && $request->user()->hasPermission($tenant, 'users.create'), 403);
        abort_if($invitation->accepted_at || $invitation->revoked_at, 422, 'Este convite não pode ser reenviado.');
        $prefix = "taggo:invitation-resend:{$tenant->id}:";
        $inviteKey = $prefix."invitation:{$invitation->id}";
        $userKey = $prefix."user:{$request->user()->id}";
        if (RateLimiter::tooManyAttempts($inviteKey, 3) || RateLimiter::tooManyAttempts($userKey, 5)) {
            $audit->record('invitation.resend_rate_limited', $invitation);

            return back()->withErrors(['invitation' => 'Limite de reenvios atingido.']);
        }
        $token = Str::random(64);
        DB::transaction(function () use ($invitation, $token, $audit): void {
            $invitation->update(['token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(3)]);
            $audit->record('invitation.resent', $invitation, [], ['public_id' => $invitation->public_id, 'expires_at' => $invitation->expires_at->toIso8601String()]);
        });
        RateLimiter::hit($inviteKey, 3600);
        RateLimiter::hit($userKey, 3600);
        Notification::route('mail', $invitation->email)->notify(new UserInvitationNotification($invitation->fresh('tenant'), $token));

        return back()->with('success', 'Convite reenviado.');
    }
}
