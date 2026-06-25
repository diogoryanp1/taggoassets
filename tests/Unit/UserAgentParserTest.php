<?php

namespace Tests\Unit;

use App\Support\UserAgent\UserAgentParser;
use PHPUnit\Framework\TestCase;

class UserAgentParserTest extends TestCase
{
    public function test_it_parses_common_agents(): void
    {
        $parser = new UserAgentParser;
        $this->assertSame('Chrome em Windows', $parser->parse('Mozilla Chrome/124 Windows')->displayName());
        $this->assertSame('Safari em iPhone', $parser->parse('Mozilla Safari/17 iPhone Mobile')->displayName());
        $this->assertSame('Dispositivo desconhecido', $parser->parse('bot')->displayName());
    }
}
