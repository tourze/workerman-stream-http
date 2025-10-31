<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Protocol;

interface HttpConstantsInterface
{
    public const CR = "\r";
    public const LF = "\n";
    public const CRLF = "\r\n";
    public const CRLF2 = "\r\n\r\n";
}
