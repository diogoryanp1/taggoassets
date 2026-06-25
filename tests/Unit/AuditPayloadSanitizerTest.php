<?php

namespace Tests\Unit;

use App\Domain\Audit\Services\AuditPayloadSanitizer;
use PHPUnit\Framework\TestCase;

class AuditPayloadSanitizerTest extends TestCase
{
    public function test_it_recursively_removes_sensitive_data(): void
    {
        $value = (new AuditPayloadSanitizer)->sanitize(['name' => 'ok', 'TOKEN' => 'x', 'nested' => ['password' => 'x', 'safe' => 1], 'api_key' => 'x']);
        $this->assertSame(['name' => 'ok', 'nested' => ['safe' => 1]], $value);
    }
}
