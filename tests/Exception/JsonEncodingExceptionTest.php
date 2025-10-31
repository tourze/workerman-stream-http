<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;
use Tourze\Workerman\StreamHTTP\Exception\JsonEncodingException;

/**
 * @internal
 */
#[CoversClass(JsonEncodingException::class)]
class JsonEncodingExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return JsonEncodingException::class;
    }

    public function testExceptionExtendsHttpProtocolException(): void
    {
        $exception = new JsonEncodingException();
        $this->assertInstanceOf(HttpProtocolException::class, $exception);
    }
}
