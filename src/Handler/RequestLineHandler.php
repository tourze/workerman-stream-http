<?php

namespace Tourze\Workerman\StreamHTTP\Handler;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;
use Tourze\Workerman\StreamHTTP\Protocol\HttpProtocol;

class RequestLineHandler implements RequestHandlerInterface
{
    private static array $allowedMethods = [
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'DELETE',
        'CONNECT',
        'OPTIONS',
        'TRACE',
        'PATCH'
    ];

    private Psr17Factory $psr17Factory;

    public function __construct(Psr17Factory $psr17Factory)
    {
        $this->psr17Factory = $psr17Factory;
    }

    public function processInput(string $buffer): int|false
    {
        // 找到第一个CRLF的位置
        $pos = strpos($buffer, HttpProtocol::CRLF);

        // 如果没找到CRLF
        if ($pos === false) {
            // 如果已经超过最大长度，返回false表示错误
            if (strlen($buffer) >= 8192) {
                return false;
            }
            // 否则返回0表示需要更多数据
            return 0;
        }

        // 返回请求行的长度（包含CRLF），这样Workerman就会截取这么长的数据
        return $pos + strlen(HttpProtocol::CRLF);
    }

    public function process(string $buffer, HttpContext $ctx): Request
    {
        // 只取第一行，不包含CRLF
        $line = substr($buffer, 0, strpos($buffer, HttpProtocol::CRLF));

        if (!preg_match('/^([A-Z]+)\s+(.+)\s+HTTP\/(\d\.\d)$/i', $line, $matches)) {
            throw new \RuntimeException('Invalid request line: ' . $line);
        }

        $method = strtoupper($matches[1]);
        if (!in_array($method, self::$allowedMethods, true)) {
            throw new \RuntimeException(sprintf('Unsupported HTTP method: %s', $method));
        }

        $uri = $this->psr17Factory->createUri($matches[2]);
        $protocolVersion = $matches[3];

        $request = new Request($method, $uri, [], '', $protocolVersion);
        $ctx->request = $request;
        $ctx->phase = HttpPhase::HEADERS;
        return $request;
    }

    public static function addAllowedMethod(string $method): void
    {
        $method = strtoupper($method);
        if (!in_array($method, self::$allowedMethods, true)) {
            self::$allowedMethods[] = $method;
        }
    }

    public static function getAllowedMethods(): array
    {
        return self::$allowedMethods;
    }
}
