<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Exception;

class ContextException extends HttpProtocolException
{
    public function __construct(string $message = 'Context error', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
