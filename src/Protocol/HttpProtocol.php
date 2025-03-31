<?php

namespace Tourze\Workerman\StreamHTTP\Protocol;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;
use Tourze\Workerman\StreamHTTP\Exception\InvalidRequestException;
use Tourze\Workerman\StreamHTTP\Handler\BodyHandler;
use Tourze\Workerman\StreamHTTP\Handler\HeadersHandler;
use Tourze\Workerman\StreamHTTP\Handler\RequestLineHandler;
use WeakMap;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\ProtocolInterface;

class HttpProtocol implements ProtocolInterface
{
    const CR = "\r";
    const LF = "\n";
    const CRLF = self::CR . self::LF;
    const CRLF2 = self::CRLF . self::CRLF;

    private static ?WeakMap $contextMap = null;
    private static ?Psr17Factory $psr17Factory = null;
    private static ?RequestLineHandler $requestLineHandler = null;
    private static ?HeadersHandler $headersHandler = null;
    private static ?BodyHandler $bodyHandler = null;

    private static function init(): void
    {
        if (self::$contextMap === null) {
            self::$contextMap = new WeakMap();
            self::$psr17Factory = new Psr17Factory();
            self::$requestLineHandler = new RequestLineHandler(self::$psr17Factory);
            self::$headersHandler = new HeadersHandler();
            self::$bodyHandler = new BodyHandler(self::$psr17Factory);
        }
    }

    private static function getContext(ConnectionInterface $connection): HttpContext
    {
        self::init();
        if (!isset(self::$contextMap[$connection])) {
            self::$contextMap[$connection] = new HttpContext();
        }
        return self::$contextMap[$connection];
    }

    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        if (!is_string($buffer)) {
            throw new InvalidRequestException('Invalid request buffer type');
        }

        if (strlen($buffer) < 2) {
            return 0;
        }

        $ctx = self::getContext($connection);

        try {
            return match ($ctx->phase) {
                HttpPhase::REQUEST_LINE => self::$requestLineHandler->processInput($buffer),
                HttpPhase::HEADERS => self::$headersHandler->processInput($buffer),
                HttpPhase::BODY => self::$bodyHandler->processInput($buffer),
            };
        } catch (\Throwable $e) {
            self::handleError($connection, $ctx, $e);
            return -1;
        }
    }

    private static function handleError(ConnectionInterface $connection, HttpContext $ctx, \Throwable $e): void
    {
        $message = $e->getMessage();
        $code = $e instanceof HttpProtocolException ? $e->getCode() : 500;

        // 只有在编码阶段才发送错误响应
        if ($ctx->phase === HttpPhase::BODY) {
            $response = sprintf(
                "HTTP/1.1 %d %s\r\n" .
                "Connection: close\r\n" .
                "Content-Type: text/plain\r\n" .
                "Content-Length: %d\r\n\r\n%s",
                $code,
                self::getStatusText($code),
                strlen($message),
                $message
            );
            $connection->send($response);
        }

        self::closeConnection($connection);
    }

    private static function getStatusText(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            408 => 'Request Timeout',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            431 => 'Request Header Fields Too Large',
            500 => 'Internal Server Error',
            default => 'Unknown Error',
        };
    }

    /**
     * 清理连接上下文并关闭连接
     */
    private static function closeConnection(ConnectionInterface $connection): void
    {
        // 清理上下文
        if (isset(self::$contextMap[$connection])) {
            unset(self::$contextMap[$connection]);
        }

        // 关闭连接
        $connection->close();
    }

    public static function decode(string $buffer, ConnectionInterface $connection): Request
    {
        if (!is_string($buffer)) {
            throw new InvalidRequestException('Invalid request buffer type');
        }

        $ctx = self::getContext($connection);

        try {
            // 根据当前阶段处理
            $request = match ($ctx->phase) {
                HttpPhase::REQUEST_LINE => self::$requestLineHandler->process($buffer, $ctx),
                HttpPhase::HEADERS => self::$headersHandler->process($buffer, $ctx),
                HttpPhase::BODY => self::$bodyHandler->process($buffer, $ctx),
            };

            // 在处理完headers后检查连接状态
            if ($ctx->phase === HttpPhase::HEADERS) {
                // 检查是否需要关闭连接
                // 1. HTTP/1.0 默认关闭
                // 2. 明确指定 Connection: close
                $protocolVersion = $request->getProtocolVersion();
                $connectionHeaders = array_map('strtolower', $request->getHeader('Connection'));

                $ctx->shouldClose = $protocolVersion === '1.0' || in_array('close', $connectionHeaders, true);
            }

            // 保存当前请求到全局变量，供BodyHandler使用
            $GLOBALS['_current_request'] = $request;

            return $request;

        } catch (\Throwable $e) {
            self::handleError($connection, $ctx, $e);
            throw $e;
        }
    }

    public static function encode(mixed $data, ConnectionInterface $connection): string
    {
        $ctx = self::getContext($connection);

        try {
            if ($data instanceof ResponseInterface) {
                // 获取响应体
                $body = (string)$data->getBody();

                // 设置基础响应头
                $data = $data->withHeader('Content-Length', strlen($body));

                // 设置连接状态
                if ($ctx->shouldClose) {
                    $data = $data->withHeader('Connection', 'close');
                } else if ($data->getProtocolVersion() === '1.0') {
                    $data = $data->withHeader('Connection', 'keep-alive');
                }

                // 构建响应
                $response = sprintf(
                    'HTTP/%s %d %s%s',
                    $data->getProtocolVersion(),
                    $data->getStatusCode(),
                    $data->getReasonPhrase(),
                    static::CRLF
                );

                foreach ($data->getHeaders() as $name => $values) {
                    $response .= sprintf('%s: %s%s', $name, implode(", ", $values), static::CRLF);
                }

                $response .= static::CRLF . $body;

                // 如果需要关闭连接，立即发送并关闭
                if ($ctx->shouldClose) {
                    $connection->send($response);
                    self::closeConnection($connection);
                    return '';
                }

                return $response;
            }
            // 如果是字符串，假定是已经格式化好的HTTP响应
            elseif (is_string($data)) {
                $response = $data;
                
                // 如果需要关闭连接，立即发送并关闭
                if ($ctx->shouldClose) {
                    $connection->send($response);
                    self::closeConnection($connection);
                    return '';
                }

                return $response;
            }
            else {
                throw new \InvalidArgumentException(sprintf(
                    'Invalid response type. Expected PSR-7 ResponseInterface or string, got %s',
                    is_object($data) ? get_class($data) : gettype($data)
                ));
            }
        } catch (\Throwable $e) {
            self::handleError($connection, $ctx, $e);
            throw $e;
        }
    }

    /**
     * 允许外部扩展HTTP方法
     */
    public static function addAllowedMethod(string $method): void
    {
        RequestLineHandler::addAllowedMethod($method);
    }

    /**
     * 获取当前支持的HTTP方法列表
     */
    public static function getAllowedMethods(): array
    {
        return RequestLineHandler::getAllowedMethods();
    }
}
