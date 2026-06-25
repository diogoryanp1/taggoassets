<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', $request->string('email'))->first();
        if ($user?->status === 'active') {
            Password::sendResetLink(['email' => $user->email]);
        }
        $audit->record('auth.password_reset_requested', null, [], ['email' => $request->string('email')->toString()]);

        return back()->with('status', 'Caso exista uma conta vinculada ao e-mail informado, as instruções de recuperação serão enviadas.');
    }
}
