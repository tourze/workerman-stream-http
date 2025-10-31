<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;
use Tourze\Workerman\StreamHTTP\Exception\InvalidResponseException;

/**
 * @internal
 */
#[CoversClass(InvalidResponseException::class)]
final class InvalidResponseExceptionTest extends AbstractExceptionTestCase
{
    public function testDefaultException(): void
    {
        $exception = new InvalidResponseException();

        $this->assertSame('Invalid response', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithMessage(): void
    {
        $exception = new InvalidResponseException('Custom message');

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }

    public function testExceptionWithCustomCode(): void
    {
        $exception = new InvalidResponseException('Custom message', 502);

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(502, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new InvalidResponseException('Custom message', 500, $previous);

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionExtendsHttpProtocolException(): void
    {
        $exception = new InvalidResponseException();

        $this->assertInstanceOf(HttpProtocolException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
