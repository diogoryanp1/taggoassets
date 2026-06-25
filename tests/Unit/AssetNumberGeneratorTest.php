<?php

namespace Tests\Unit;

use App\Domain\Assets\Services\AssetNumberGenerator;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequence_is_scoped_by_tenant_and_increments_without_max_query(): void
    {
        $first = Tenant::factory()->create();
        $second = Tenant::factory()->create();
        $generator = app(AssetNumberGenerator::class);

        $this->assertSame('PAT-2026-000001', $generator->generate($first, 2026));
        $this->assertSame('PAT-2026-000002', $generator->generate($first, 2026));
        $this->assertSame('PAT-2026-000001', $generator->generate($second, 2026));
    }
}
