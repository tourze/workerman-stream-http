<?php

namespace Tourze\Workerman\StreamHTTP\Tests\Unit\Context;

use Nyholm\Psr7\Request;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;

class HttpContextTest extends TestCase
{
    public function testContextInitialization(): void
    {
        $context = new HttpContext();
        
        $this->assertSame(HttpPhase::REQUEST_LINE, $context->phase);
        $this->assertNull($context->request);
        $this->assertFalse($context->shouldClose);
    }

    public function testContextWithRequest(): void
    {
        $context = new HttpContext();
        $request = new Request('GET', '/test');
        
        $context->request = $request;
        $context->phase = HttpPhase::HEADERS;
        
        $this->assertSame($request, $context->request);
        $this->assertSame(HttpPhase::HEADERS, $context->phase);
    }

    public function testContextShouldClose(): void
    {
        $context = new HttpContext();
        $context->shouldClose = true;
        
        $this->assertTrue($context->shouldClose);
    }
}