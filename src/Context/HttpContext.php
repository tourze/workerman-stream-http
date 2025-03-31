<?php

namespace Tourze\Workerman\StreamHTTP\Context;

use Nyholm\Psr7\Request;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;

class HttpContext
{
    public HttpPhase $phase = HttpPhase::REQUEST_LINE;
    public ?Request $request = null;
    public bool $shouldClose = false;
}
