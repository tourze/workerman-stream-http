<?php

namespace Tourze\Workerman\StreamHTTP\Exception;

class InvalidRequestException extends HttpProtocolException
{
    public function __construct(string $message = "Invalid request", int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
