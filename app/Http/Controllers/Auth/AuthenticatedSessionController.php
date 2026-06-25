<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $key = Str::lower($request->input('email')).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Não foi possível autenticar com as credenciais informadas.'])->onlyInput('email');
        }
        $user = User::where('email', $request->input('email'))->first();
        if (! $user || $user->status !== 'active' || ! Auth::attempt(['email' => $request->input('email'), 'password' => $request->input('password')], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            $audit->record('auth.login_failed', null, [], ['email' => $request->input('email')]);

            return back()->withErrors(['email' => 'Não foi possível autenticar com as credenciais informadas.'])->onlyInput('email');
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        $audit->record('auth.login', $user);
        $tenant = $user->tenants()->wherePivot('status', 'active')->orderBy('name')->first();
        if ($tenant) {
            $request->session()->put('active_tenant', $tenant->public_id);
        }

        $request->session()->forget('url.intended');

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request, AuditLogger $audit): RedirectResponse
    {
        $audit->record('auth.logout', $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
