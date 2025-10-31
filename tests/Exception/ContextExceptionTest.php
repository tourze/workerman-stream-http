<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\StreamHTTP\Exception\ContextException;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;

/**
 * @internal
 */
#[CoversClass(ContextException::class)]
final class ContextExceptionTest extends AbstractExceptionTestCase
{
    public function testDefaultException(): void
    {
        $exception = new ContextException();

        $this->assertSame('Context error', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithMessage(): void
    {
        $exception = new ContextException('Custom message');

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }

    public function testExceptionWithCustomCode(): void
    {
        $exception = new ContextException('Custom message', 503);

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(503, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new ContextException('Custom message', 500, $previous);

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionExtendsHttpProtocolException(): void
    {
        $exception = new ContextException();

        $this->assertInstanceOf(HttpProtocolException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
