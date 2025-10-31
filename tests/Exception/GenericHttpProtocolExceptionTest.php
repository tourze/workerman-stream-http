<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\StreamHTTP\Exception\GenericHttpProtocolException;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;

/**
 * @internal
 */
#[CoversClass(GenericHttpProtocolException::class)]
class GenericHttpProtocolExceptionTest extends AbstractExceptionTestCase
{
    public function testIsInstanceOfHttpProtocolException(): void
    {
        $exception = new GenericHttpProtocolException();

        $this->assertInstanceOf(HttpProtocolException::class, $exception);
    }

    public function testDefaultMessage(): void
    {
        $exception = new GenericHttpProtocolException();

        $this->assertSame('HTTP protocol error', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }

    public function testCustomMessage(): void
    {
        $exception = new GenericHttpProtocolException('Custom message', 400);

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
    }

    public function testWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new GenericHttpProtocolException('Test message', 500, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
