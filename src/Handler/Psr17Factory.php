<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Handler;

class Psr17Factory
{
    private static ?\Nyholm\Psr7\Factory\Psr17Factory $instance = null;

    public static function getInstance(): \Nyholm\Psr7\Factory\Psr17Factory
    {
        if (null === self::$instance) {
            self::$instance = new \Nyholm\Psr7\Factory\Psr17Factory();
        }

        return self::$instance;
    }
}
