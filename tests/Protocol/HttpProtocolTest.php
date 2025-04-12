<?php

namespace Tourze\Workerman\StreamHTTP\Tests\Protocol;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;
use Workerman\Connection\TcpConnection;

class HttpProtocolTest extends TestCase
{
    /**
     * @var TcpConnection&\PHPUnit\Framework\MockObject\MockObject
     */
    private $connection;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建一个模拟的连接对象
        $this->connection = $this->createMock(TcpConnection::class);
    }

    /**
     * 测试添加和获取允许的HTTP方法
     */
    public function testAddAndGetAllowedMethods(): void
    {
        // 添加一个自定义方法
        HttpProtocol::addAllowedMethod('CUSTOM');

        // 获取所有允许的方法
        $methods = HttpProtocol::getAllowedMethods();

        // 验证新方法已添加
        $this->assertContains('CUSTOM', $methods);

        // 验证标准方法也存在
        $this->assertContains('GET', $methods);
        $this->assertContains('POST', $methods);
    }

    /**
     * 测试编码响应对象
     */
    public function testEncodeResponse(): void
    {
        // 创建一个PSR-7响应对象
        $response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            'Hello World!'
        );

        // 编码响应
        $encodedResponse = HttpProtocol::encode($response, $this->connection);

        // 验证编码的响应包含必要的组件
        $this->assertStringContainsString('HTTP/1.1 200 OK', $encodedResponse);
        $this->assertStringContainsString('Content-Type: text/plain', $encodedResponse);
        $this->assertStringContainsString('Content-Length: 12', $encodedResponse);
        $this->assertStringContainsString('Hello World!', $encodedResponse);
    }

    /**
     * 测试编码字符串响应
     */
    public function testEncodeStringResponse(): void
    {
        $responseString = "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\n\r\nHello World!";

        // 编码响应
        $encodedResponse = HttpProtocol::encode($responseString, $this->connection);

        // 验证编码的响应与原始响应相同
        $this->assertEquals($responseString, $encodedResponse);
    }

    /**
     * 测试编码无效响应类型
     */
    public function testEncodeInvalidResponseType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // 尝试编码一个无效的响应类型（整数）
        HttpProtocol::encode(123, $this->connection);
    }
}
