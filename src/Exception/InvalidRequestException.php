<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Exception;

/**
 * 当HTTP请求无效时抛出的异常
 */
class InvalidRequestException extends HttpProtocolException
{
    /**
     * 不同类型无效请求的错误码
     */
    public const ERROR_INVALID_REQUEST_LINE = 4001;
    public const ERROR_UNSUPPORTED_METHOD = 4002;
    public const ERROR_REQUEST_TOO_LONG = 4003;
    public const ERROR_MISSING_CRLF = 4004;

    public function __construct(string $message = 'Invalid request', int $code = self::ERROR_INVALID_REQUEST_LINE, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
