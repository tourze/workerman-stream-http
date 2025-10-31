<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Exception;

abstract class HttpProtocolException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 400, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
