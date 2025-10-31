<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Handler;

use Nyholm\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;
use Tourze\Workerman\StreamHTTP\Handler\HeadersHandler;

/**
 * @internal
 */
#[CoversClass(HeadersHandler::class)]
final class HeadersHandlerTest extends TestCase
{
    private HeadersHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new HeadersHandler();
    }

    public function testProcessInputWithCompleteHeaders(): void
    {
        $buffer = "Host: example.com\r\nContent-Type: text/html\r\n\r\n";

        $result = $this->handler->processInput($buffer);

        $this->assertSame(strlen($buffer), $result);
    }

    public function testProcessInputWithIncompleteHeaders(): void
    {
        $buffer = "Host: example.com\r\nContent-Type: text/html\r\n";

        $result = $this->handler->processInput($buffer);

        $this->assertSame(0, $result);
    }

    public function testProcessInputWithTooLongHeaders(): void
    {
        // 创建一个超过16384字节但没有结束标记的缓冲区
        $buffer = str_repeat('A', 16400);

        $result = $this->handler->processInput($buffer);

        $this->assertFalse($result);
    }

    public function testProcess(): void
    {
        $context = new HttpContext();
        $request = new Request('GET', '/test');
        $context->request = $request;

        $buffer = "Host: example.com\r\nContent-Type: text/html\r\nContent-Length: 10\r\n\r\n";

        $result = $this->handler->process($buffer, $context);

        $this->assertSame('example.com', $result->getHeaderLine('Host'));
        $this->assertSame('text/html', $result->getHeaderLine('Content-Type'));
        $this->assertSame('10', $result->getHeaderLine('Content-Length'));
        $this->assertSame(HttpPhase::BODY, $context->phase);
    }

    public function testProcessWithKeepAlive(): void
    {
        $context = new HttpContext();
        $request = new Request('GET', '/test');
        $context->request = $request;

        $buffer = "Host: example.com\r\nConnection: keep-alive\r\n\r\n";

        $result = $this->handler->process($buffer, $context);

        $this->assertSame('keep-alive', $result->getHeaderLine('Connection'));
        $this->assertFalse($context->shouldClose);
    }

    public function testProcessWithClose(): void
    {
        $context = new HttpContext();
        $request = new Request('GET', '/test');
        $context->request = $request;

        $buffer = "Host: example.com\r\nConnection: close\r\n\r\n";

        $result = $this->handler->process($buffer, $context);

        $this->assertSame('close', $result->getHeaderLine('Connection'));
        $this->assertTrue($context->shouldClose);
    }
}
