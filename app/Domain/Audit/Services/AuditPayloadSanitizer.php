<?php

namespace App\Domain\Audit\Services;

class AuditPayloadSanitizer
{
    private const EXACT = ['password', 'password_confirmation', 'current_password', 'token', 'token_hash', 'remember_token', 'authorization', 'cookie', 'session', 'session_id', 'encrypted_session_id', 'fingerprint', 'secret', 'mfa_secret', 'api_key'];

    public function sanitize(array $payload): array
    {
        $result = [];
        foreach ($payload as $key => $value) {
            $normalized = strtolower((string) $key);
            if (in_array($normalized, self::EXACT, true) || str_ends_with($normalized, '_token') || str_ends_with($normalized, '_secret') || str_contains($normalized, 'password')) {
                continue;
            }
            $result[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $result;
    }
}
