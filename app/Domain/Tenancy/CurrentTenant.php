<?php

namespace App\Domain\Tenancy;

use App\Domain\Tenancy\Models\Tenant;

final class CurrentTenant
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function clear(): void
    {
        $this->tenant = null;
    }

    public function get(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): int
    {
        return $this->require()->id;
    }

    public function require(): Tenant
    {
        return $this->tenant ?? throw new \LogicException('No active tenant.');
    }
}
