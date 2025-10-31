<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Handler;

use Nyholm\Psr7\Request;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;

interface RequestHandlerInterface
{
    public function processInput(string $buffer): int|false;

    public function process(string $buffer, HttpContext $ctx): Request;
}
