<?php

namespace Tourze\Workerman\StreamHTTP\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum HttpPhase implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case REQUEST_LINE;
    case HEADERS;
    case BODY;

    public function getLabel(): string
    {
        return match($this) {
            self::REQUEST_LINE => 'Request Line',
            self::HEADERS => 'Headers',
            self::BODY => 'Body',
        };
    }
}
