<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Protocol;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Tourze\Workerman\StreamHTTP\Context\ContextMap;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;
use Tourze\Workerman\StreamHTTP\Exception\GenericHttpProtocolException;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;
use Tourze\Workerman\StreamHTTP\Exception\InvalidResponseException;
use Tourze\Workerman\StreamHTTP\Handler\BodyHandler;
use Tourze\Workerman\StreamHTTP\Handler\HeadersHandler;
use Tourze\Workerman\StreamHTTP\Handler\RequestLineHandler;
use Workerman\Connection\ConnectionInterface;
use Workerman\Protocols\ProtocolInterface;

class HttpProtocol implements ProtocolInterface, HttpConstantsInterface
{
    private static ?RequestLineHandler $requestLineHandler = null;

    private static ?BodyHandler $bodyHandler = null;

    private static function init(): void
    {
        if (null === self::$bodyHandler) {
            $psr17Factory = new Psr17Factory();
            self::$requestLineHandler = new RequestLineHandler($psr17Factory);
            self::$bodyHandler = new BodyHandler($psr17Factory);
        }
    }

    private static function getContext(ConnectionInterface $connection): HttpContext
    {
        self::init();

        if (!ContextMap::getContextMap()->offsetExists($connection)) {
            ContextMap::getContextMap()->offsetSet($connection, new HttpContext());
        }

        return ContextMap::getContextMap()->offsetGet($connection);
    }

    public static function input(string $buffer, ConnectionInterface $connection): int
    {
        if (strlen($buffer) < 2) {
            return 0;
        }

        $ctx = self::getContext($connection);

        try {
            $result = match ($ctx->phase) {
                HttpPhase::REQUEST_LINE => self::$requestLineHandler?->processInput($buffer) ?? false,
                HttpPhase::HEADERS => HeadersHandler::getInstance()->processInput($buffer),
                HttpPhase::BODY => self::$bodyHandler?->processInput($buffer) ?? false,
            };

            if (is_int($result)) {
                return $result;
            }

            // $result is false here, return -1 for error
            return -1;
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
        if (HttpPhase::BODY === $ctx->phase) {
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
        ContextMap::getContextMap()->offsetUnset($connection);

        // 清理当前请求
        self::$bodyHandler?->setCurrentRequest(null);

        // 关闭连接
        $connection->close();
    }

    public static function decode(string $buffer, ConnectionInterface $connection): Request
    {
        $ctx = self::getContext($connection);

        try {
            // 根据当前阶段处理
            $request = match ($ctx->phase) {
                HttpPhase::REQUEST_LINE => self::$requestLineHandler?->process($buffer, $ctx),
                HttpPhase::HEADERS => HeadersHandler::getInstance()->process($buffer, $ctx),
                HttpPhase::BODY => self::$bodyHandler?->process($buffer, $ctx),
            };

            if (null === $request) {
                throw new GenericHttpProtocolException('Handler returned null request');
            }

            // 在处理完headers后检查连接状态
            if (HttpPhase::HEADERS === $ctx->phase) {
                // 检查是否需要关闭连接
                // 1. HTTP/1.0 默认关闭
                // 2. 明确指定 Connection: close
                $protocolVersion = $request->getProtocolVersion();
                $connectionHeaders = array_map('strtolower', $request->getHeader('Connection'));

                $ctx->shouldClose = '1.0' === $protocolVersion || in_array('close', $connectionHeaders, true);
            }

            // 保存当前请求，供BodyHandler使用
            self::$bodyHandler?->setCurrentRequest($request);

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
                return self::encodeResponseInterface($data, $connection, $ctx);
            }

            if (is_string($data)) {
                return self::encodeStringResponse($data, $connection, $ctx);
            }

            throw new InvalidResponseException(sprintf('Invalid response type. Expected PSR-7 ResponseInterface or string, got %s', is_object($data) ? get_class($data) : gettype($data)));
        } catch (\Throwable $e) {
            self::handleError($connection, $ctx, $e);
            throw $e;
        }
    }

    private static function encodeResponseInterface(ResponseInterface $data, ConnectionInterface $connection, HttpContext $ctx): string
    {
        // 获取响应体
        $body = (string) $data->getBody();

        // 设置基础响应头
        $data = $data->withHeader('Content-Length', (string) strlen($body));

        // 设置连接状态
        $data = self::configureConnectionHeaders($data, $ctx);

        // 构建响应
        $response = self::buildHttpResponse($data, $body);

        return self::handleConnectionClose($response, $connection, $ctx);
    }

    private static function encodeStringResponse(string $data, ConnectionInterface $connection, HttpContext $ctx): string
    {
        return self::handleConnectionClose($data, $connection, $ctx);
    }

    private static function configureConnectionHeaders(ResponseInterface $response, HttpContext $ctx): ResponseInterface
    {
        if ($ctx->shouldClose) {
            return $response->withHeader('Connection', 'close');
        }

        if ('1.0' === $response->getProtocolVersion()) {
            return $response->withHeader('Connection', 'keep-alive');
        }

        return $response;
    }

    private static function buildHttpResponse(ResponseInterface $response, string $body): string
    {
        $httpResponse = sprintf(
            'HTTP/%s %d %s%s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase(),
            self::CRLF
        );

        foreach ($response->getHeaders() as $name => $values) {
            $httpResponse .= sprintf('%s: %s%s', $name, implode(', ', $values), self::CRLF);
        }

        return $httpResponse . self::CRLF . $body;
    }

    private static function handleConnectionClose(string $response, ConnectionInterface $connection, HttpContext $ctx): string
    {
        if ($ctx->shouldClose) {
            $connection->send($response);
            self::closeConnection($connection);

            return '';
        }

        return $response;
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
     *
     * @return array<string>
     */
    public static function getAllowedMethods(): array
    {
        return RequestLineHandler::getAllowedMethods();
    }
}
