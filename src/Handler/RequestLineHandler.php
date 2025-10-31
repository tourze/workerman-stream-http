<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Handler;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;
use Tourze\Workerman\StreamHTTP\Exception\InvalidRequestException;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;

class RequestLineHandler implements RequestHandlerInterface
{
    /**
     * 请求行的最大允许长度
     */
    private const MAX_REQUEST_LINE_LENGTH = 8192;

    /**
     * @var array<string>
     */
    private static array $allowedMethods = [
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'DELETE',
        'CONNECT',
        'OPTIONS',
        'TRACE',
        'PATCH',
    ];

    public function __construct(private readonly Psr17Factory $psr17Factory)
    {
    }

    /**
     * 处理输入缓冲区并判断是否有完整的请求行
     *
     * @param string $buffer 要处理的输入缓冲区
     * @return int|false 返回请求行长度（包含CRLF），0表示需要更多数据，false表示错误
     */
    public function processInput(string $buffer): int|false
    {
        // 找到第一个CRLF的位置
        $pos = strpos($buffer, HttpProtocol::CRLF);

        // 如果没找到CRLF
        if (false === $pos) {
            // 如果已经超过最大长度，返回false表示错误
            if (strlen($buffer) >= self::MAX_REQUEST_LINE_LENGTH) {
                return false;
            }

            // 否则返回0表示需要更多数据
            return 0;
        }

        // 返回请求行的长度（包含CRLF），这样Workerman就会截取这么长的数据
        return $pos + strlen(HttpProtocol::CRLF);
    }

    /**
     * 处理请求行并创建HTTP请求对象
     *
     * @param string $buffer 包含请求行的输入缓冲区
     * @param HttpContext $ctx 要更新的HTTP上下文
     * @return Request 创建的HTTP请求对象
     * @throws InvalidRequestException 当请求行无效或方法不支持时
     */
    public function process(string $buffer, HttpContext $ctx): Request
    {
        // 只取第一行，不包含CRLF
        $crlfPos = strpos($buffer, HttpProtocol::CRLF);
        if (false === $crlfPos) {
            throw new InvalidRequestException('No CRLF found in buffer', InvalidRequestException::ERROR_MISSING_CRLF);
        }
        $line = substr($buffer, 0, $crlfPos);

        if (1 !== preg_match('/^([A-Z]+)\s+(.+)\s+HTTP\/(\d\.\d)$/i', $line, $matches)) {
            throw new InvalidRequestException('Invalid request line: ' . $line, InvalidRequestException::ERROR_INVALID_REQUEST_LINE);
        }

        $method = strtoupper($matches[1]);
        if (!in_array($method, self::$allowedMethods, true)) {
            throw new InvalidRequestException(sprintf('Unsupported HTTP method: %s', $method), InvalidRequestException::ERROR_UNSUPPORTED_METHOD);
        }

        $uri = $this->psr17Factory->createUri($matches[2]);
        $protocolVersion = $matches[3];

        $request = new Request($method, $uri, [], '', $protocolVersion);
        $ctx->request = $request;
        $ctx->phase = HttpPhase::HEADERS;

        return $request;
    }

    /**
     * 添加新的HTTP方法到允许的方法列表
     *
     * @param string $method 要添加的HTTP方法（如 'PURGE', 'PROPFIND'）
     */
    public static function addAllowedMethod(string $method): void
    {
        $method = strtoupper($method);
        if (!in_array($method, self::$allowedMethods, true)) {
            self::$allowedMethods[] = $method;
        }
    }

    /**
     * @return array<string>
     */
    public static function getAllowedMethods(): array
    {
        return self::$allowedMethods;
    }
}
