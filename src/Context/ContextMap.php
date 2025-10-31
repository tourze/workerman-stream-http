<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Context;

class ContextMap
{
    /**
     * @var \WeakMap<object, HttpContext>|null
     */
    private static ?\WeakMap $contextMap = null;

    /**
     * @return \WeakMap<object, HttpContext>
     */
    public static function getContextMap(): \WeakMap
    {
        return self::$contextMap ??= new \WeakMap();
    }
}
