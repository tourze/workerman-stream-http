<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Handler;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;
use Tourze\Workerman\StreamHTTP\Handler\RequestLineHandler;

/**
 * @internal
 */
#[CoversClass(RequestLineHandler::class)]
final class RequestLineHandlerTest extends TestCase
{
    private RequestLineHandler $handler;

    private HttpContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $psr17Factory = new Psr17Factory();
        $this->handler = new RequestLineHandler($psr17Factory);
        $this->context = new HttpContext();
    }

    /**
     * 测试正常请求行的处理
     */
    public function testProcessValidRequestLine(): void
    {
        $buffer = "GET /path/to/resource?query=value HTTP/1.1\r\n";

        $request = $this->handler->process($buffer, $this->context);

        // 验证请求对象的属性
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/path/to/resource', $request->getUri()->getPath());
        $this->assertEquals('query=value', $request->getUri()->getQuery());
        $this->assertEquals('1.1', $request->getProtocolVersion());

        // 验证上下文状态转换
        $this->assertEquals(HttpPhase::HEADERS, $this->context->phase);
        $this->assertSame($request, $this->context->request);
    }

    /**
     * 测试无效的请求行
     */
    public function testInvalidRequestLine(): void
    {
        $this->expectException(\RuntimeException::class);

        $buffer = "INVALID REQUEST LINE\r\n";
        $this->handler->process($buffer, $this->context);
    }

    /**
     * 测试不支持的HTTP方法
     */
    public function testUnsupportedMethod(): void
    {
        $this->expectException(\RuntimeException::class);

        $buffer = "UNSUPPORTED /path HTTP/1.1\r\n";
        $this->handler->process($buffer, $this->context);
    }

    /**
     * 测试输入处理函数 - 完整请求行
     */
    public function testProcessInputComplete(): void
    {
        $buffer = "GET /path HTTP/1.1\r\nHost: example.com\r\n";

        // 应该返回第一个CRLF的位置 + CRLF长度
        $result = $this->handler->processInput($buffer);

        $crlfPos = strpos($buffer, "\r\n");
        $expectedLength = (false !== $crlfPos ? $crlfPos : 0) + 2;
        $this->assertEquals($expectedLength, $result);
    }

    /**
     * 测试输入处理函数 - 不完整的请求行
     */
    public function testProcessInputIncomplete(): void
    {
        $buffer = 'GET /path HTTP/1.1';

        // 应该返回0，表示需要更多数据
        $result = $this->handler->processInput($buffer);

        $this->assertEquals(0, $result);
    }

    /**
     * 测试处理过长的请求行
     */
    public function testProcessInputTooLong(): void
    {
        // 创建一个超过8192字节的长请求行
        $buffer = 'GET /' . str_repeat('a', 8200) . ' HTTP/1.1';

        // 应该返回false，表示错误
        $result = $this->handler->processInput($buffer);

        $this->assertFalse($result);
    }
}
