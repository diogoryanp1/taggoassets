<?php

namespace Tests\Unit;

use App\Support\Pagination\PaginationResolver;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class PaginationResolverTest extends TestCase
{
    public function test_it_normalizes_pagination(): void
    {
        $resolver = new PaginationResolver;
        $this->assertSame(20, $resolver->resolve(Request::create('/', 'GET')));
        $this->assertSame(100, $resolver->resolve(Request::create('/?per_page=1000')));
        $this->assertSame(1, $resolver->resolve(Request::create('/?per_page=-1')));
        $this->assertSame(20, $resolver->resolve(Request::create('/?per_page[]=1')));
    }
}
