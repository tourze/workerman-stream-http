<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;
use Workerman\Connection\TcpConnection;

/**
 * @internal
 */
#[CoversClass(HttpProtocol::class)]
final class HttpRequestTest extends TestCase
{
    /**
     * @var TcpConnection&MockObject
     */
    private $connection;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建一个模拟的 TcpConnection
        $this->connection = $this->createMock(TcpConnection::class);
    }

    /**
     * 测试完整 GET 请求的处理
     */
    public function testProcessGetRequest(): void
    {
        // GET 请求示例
        $requestLine = "GET /test?param=value HTTP/1.1\r\n";
        $headers = "Host: example.com\r\nUser-Agent: PHPUnit\r\n\r\n";

        // 处理请求行
        $inputResult = HttpProtocol::input($requestLine, $this->connection);
        $this->assertGreaterThan(0, $inputResult);

        // 解码请求行
        $request = HttpProtocol::decode($requestLine, $this->connection);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', $request->getUri()->getPath());

        // 处理请求头
        $inputResult = HttpProtocol::input($headers, $this->connection);
        $this->assertGreaterThan(0, $inputResult);

        // 解码请求头
        $request = HttpProtocol::decode($headers, $this->connection);
        $this->assertEquals('example.com', $request->getHeaderLine('Host'));
        $this->assertEquals('PHPUnit', $request->getHeaderLine('User-Agent'));

        // 创建响应
        $response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            'Test Response'
        );

        // 编码响应并验证响应格式
        $encodedResponse = HttpProtocol::encode($response, $this->connection);
        $this->assertStringContainsString('HTTP/1.1 200 OK', $encodedResponse);
        $this->assertStringContainsString('Content-Type: text/plain', $encodedResponse);
        $this->assertStringContainsString('Content-Length:', $encodedResponse);
        $this->assertStringContainsString('Test Response', $encodedResponse);
    }

    /**
     * 测试带请求体的 POST 请求处理
     */
    public function testProcessPostRequestWithBody(): void
    {
        // POST 请求示例
        $requestLine = "POST /api/resource HTTP/1.1\r\n";
        $headers = "Host: example.com\r\nContent-Type: application/json\r\nContent-Length: 25\r\n\r\n";
        $body = '{"key":"value","id":123}';

        // 处理请求行
        HttpProtocol::input($requestLine, $this->connection);
        HttpProtocol::decode($requestLine, $this->connection);

        // 处理请求头
        HttpProtocol::input($headers, $this->connection);
        HttpProtocol::decode($headers, $this->connection);

        // 处理请求体
        HttpProtocol::input($body, $this->connection);
        $request = HttpProtocol::decode($body, $this->connection);

        // 验证请求体
        $this->assertEquals($body, (string) $request->getBody());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));

        // 创建响应
        $response = new Response(
            200,
            ['Content-Type' => 'application/json'],
            '{"status":"success"}'
        );

        // 编码响应并验证响应格式
        $encodedResponse = HttpProtocol::encode($response, $this->connection);
        $this->assertStringContainsString('HTTP/1.1 200 OK', $encodedResponse);
        $this->assertStringContainsString('Content-Type: application/json', $encodedResponse);
        $this->assertStringContainsString('Content-Length:', $encodedResponse);
        $this->assertStringContainsString('{"status":"success"}', $encodedResponse);
    }
}
