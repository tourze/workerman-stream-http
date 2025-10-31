<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Handler;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Exception\ContextException;
use Tourze\Workerman\StreamHTTP\Handler\BodyHandler;

/**
 * @internal
 */
#[CoversClass(BodyHandler::class)]
final class BodyHandlerTest extends TestCase
{
    private BodyHandler $handler;

    private Psr17Factory $psr17Factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->psr17Factory = new Psr17Factory();
        $this->handler = new BodyHandler($this->psr17Factory);
    }

    public function testProcessInputWithGetRequest(): void
    {
        $this->handler->setCurrentRequest(new Request('GET', '/test'));

        $result = $this->handler->processInput('test body');

        $this->assertSame(9, $result); // length of 'test body'

        $this->handler->setCurrentRequest(null);
    }

    public function testProcessInputWithNoContentLength(): void
    {
        $request = new Request('POST', '/test');
        $this->handler->setCurrentRequest($request);

        $result = $this->handler->processInput('test body');

        $this->assertSame(9, $result); // length of 'test body'

        $this->handler->setCurrentRequest(null);
    }

    public function testProcessInputWithContentLength(): void
    {
        $request = new Request('POST', '/test', ['Content-Length' => '5']);
        $this->handler->setCurrentRequest($request);

        $result = $this->handler->processInput('test body');

        $this->assertSame(5, $result);

        $this->handler->setCurrentRequest(null);
    }

    public function testProcessInputWithTooLargeContent(): void
    {
        $request = new Request('POST', '/test', ['Content-Length' => '3000000']); // 3MB
        $this->handler->setCurrentRequest($request);

        $result = $this->handler->processInput('test');

        $this->assertFalse($result);

        $this->handler->setCurrentRequest(null);
    }

    public function testProcessWithoutRequest(): void
    {
        $context = new HttpContext();

        $this->expectException(ContextException::class);
        $this->expectExceptionMessage('No request in context');

        $this->handler->process('test body', $context);
    }

    public function testProcessWithGetRequest(): void
    {
        $context = new HttpContext();
        $request = new Request('GET', '/test');
        $context->request = $request;

        $this->handler->setCurrentRequest($request);

        $result = $this->handler->process('test body', $context);

        $this->assertSame($request, $result);

        $this->handler->setCurrentRequest(null);
    }

    public function testProcessWithPostRequest(): void
    {
        $context = new HttpContext();
        $request = new Request('POST', '/test', ['Content-Type' => 'text/plain']);
        $context->request = $request;

        $this->handler->setCurrentRequest($request);

        $result = $this->handler->process('test body', $context);

        $this->assertNotSame($request, $result);
        $this->assertSame('POST', $result->getMethod());
        $this->assertSame('test body', (string) $result->getBody());

        $this->handler->setCurrentRequest(null);
    }
}
