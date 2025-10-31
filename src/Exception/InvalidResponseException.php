<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Exception;

class InvalidResponseException extends HttpProtocolException
{
    public function __construct(string $message = 'Invalid response', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
