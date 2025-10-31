<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Handler;

use Nyholm\Psr7\Request;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;
use Tourze\Workerman\StreamHTTP\Exception\ContextNotInitializedException;
use Tourze\Workerman\StreamHTTP\Protocol\HttpConstantsInterface;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;

class HeadersHandler implements RequestHandlerInterface
{
    /**
     * 头部部分的最大允许长度
     */
    private const MAX_HEADERS_LENGTH = 16384;

    private static ?HeadersHandler $instance = null;

    /**
     * 获取HeadersHandler的单例实例
     *
     * @return HeadersHandler 单例实例
     */
    public static function getInstance(): HeadersHandler
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * 处理输入缓冲区并判断是否有完整的头部
     *
     * @param string $buffer 要处理的输入缓冲区
     * @return int|false 返回头部长度（包含CRLF2），0表示需要更多数据，false表示错误
     */
    public function processInput(string $buffer): int|false
    {
        // 找到头部结束标记（两个CRLF）的位置
        $pos = strpos($buffer, HttpConstantsInterface::CRLF2);

        // 如果没找到头部结束标记
        if (false === $pos) {
            // 如果已经超过最大长度，返回false表示错误
            if (strlen($buffer) >= self::MAX_HEADERS_LENGTH) {
                return false;
            }

            // 否则返回0表示需要更多数据
            return 0;
        }

        // 返回到头部结束位置的长度，这样Workerman就会截取这么长的数据
        return $pos + strlen(HttpConstantsInterface::CRLF2);
    }

    /**
     * 处理头部并更新HTTP请求对象
     *
     * @param string $buffer 包含头部的输入缓冲区
     * @param HttpContext $ctx 要更新的HTTP上下文
     * @return Request 更新的HTTP请求对象
     * @throws ContextNotInitializedException 当上下文中没有找到请求时
     */
    public function process(string $buffer, HttpContext $ctx): Request
    {
        // 解析所有头部行
        $headerLines = explode(HttpConstantsInterface::CRLF, rtrim($buffer, HttpConstantsInterface::CRLF2));
        $request = $ctx->request;

        if (null === $request) {
            throw new ContextNotInitializedException('No request found in context');
        }

        foreach ($headerLines as $line) {
            if ('' === $line) {
                continue;
            }
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $name = trim($name);
                $value = trim($value);
                $request = $request->withHeader($name, $value);

                // 检查 Connection 头部来决定是否应该关闭连接
                if ('connection' === strtolower($name)) {
                    $ctx->shouldClose = 'close' === strtolower($value);
                }
            }
        }

        $ctx->request = $request;
        $ctx->phase = HttpPhase::BODY;

        return $request;
    }
}
