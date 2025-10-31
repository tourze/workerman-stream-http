<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Exception;

class ContextNotInitializedException extends HttpProtocolException
{
    public function __construct(string $message = 'Request context not properly initialized', int $code = 500, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
