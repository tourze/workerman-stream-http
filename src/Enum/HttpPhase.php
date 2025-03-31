<?php

namespace Tourze\Workerman\StreamHTTP\Enum;

enum HttpPhase
{
    case REQUEST_LINE;
    case HEADERS;
    case BODY;
}
