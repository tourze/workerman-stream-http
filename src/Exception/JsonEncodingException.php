<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Exception;

class JsonEncodingException extends HttpProtocolException
{
    public function __construct(string $message = 'Failed to encode JSON response', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
