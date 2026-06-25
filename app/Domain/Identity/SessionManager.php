<?php

namespace App\Domain\Identity;

use App\Domain\Identity\Models\UserSession;
use App\Models\User;
use Illuminate\Http\Request;

class SessionManager
{
    public function track(Request $request): void
    {
        if (! $user = $request->user()) {
            return;
        }
        $id = $request->session()->getId();
        $fingerprint = hash_hmac('sha256', $id, config('app.key'));
        UserSession::updateOrCreate(['session_fingerprint' => $fingerprint], ['user_id' => $user->id, 'session_id_encrypted' => $id, 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'last_activity_at' => now(), 'revoked_at' => null]);
    }

    public function revoke(UserSession $session): void
    {
        app('session')->getHandler()->destroy($session->session_id_encrypted);
        $session->update(['revoked_at' => now()]);
    }

    public function revokeAll(User $user, ?string $exceptSessionId = null): void
    {
        UserSession::query()->where('user_id', $user->id)->whereNull('revoked_at')->get()->each(function (UserSession $session) use ($exceptSessionId): void {
            if ($exceptSessionId && hash_equals($exceptSessionId, $session->session_id_encrypted)) {
                return;
            } $this->revoke($session);
        });
    }
}
