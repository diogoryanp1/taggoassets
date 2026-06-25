<?php

namespace App\Support\UserAgent;

class ParsedUserAgent
{
    public function __construct(public readonly string $browser, public readonly string $operatingSystem, public readonly string $deviceType) {}

    public function displayName(): string
    {
        return $this->browser === 'Desconhecido' ? 'Dispositivo desconhecido' : "{$this->browser} em {$this->operatingSystem}";
    }
}
