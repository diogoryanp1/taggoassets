<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Identity\Models\UserSession;
use App\Domain\Identity\SessionManager;
use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request, PaginationResolver $pagination): View
    {
        return view('sessions.index', ['sessions' => $request->user()->sessions()->latest('last_activity_at')->paginate($pagination->resolve($request))]);
    }

    public function destroy(Request $request, UserSession $session, SessionManager $sessions, AuditLogger $audit): RedirectResponse
    {
        abort_unless($session->user_id === $request->user()->id, 404);
        $sessions->revoke($session);
        $audit->record('session.revoked', $session);

        return back()->with('success', 'Sessão encerrada.');
    }

    public function destroyOthers(Request $request, SessionManager $sessions, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $sessions->revokeAll($request->user(), $request->session()->getId());
        $audit->record('session.others_revoked');

        return back()->with('success', 'Outras sessões encerradas.');
    }
}
