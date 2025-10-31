<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;
use Tourze\Workerman\StreamHTTP\Exception\InvalidRequestException;

/**
 * @internal
 */
#[CoversClass(InvalidRequestException::class)]
final class InvalidRequestExceptionTest extends AbstractExceptionTestCase
{
    public function testDefaultException(): void
    {
        $exception = new InvalidRequestException();

        $this->assertSame('Invalid request', $exception->getMessage());
        $this->assertSame(InvalidRequestException::ERROR_INVALID_REQUEST_LINE, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithMessage(): void
    {
        $exception = new InvalidRequestException('Custom message');

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(InvalidRequestException::ERROR_INVALID_REQUEST_LINE, $exception->getCode());
    }

    public function testExceptionWithCustomCode(): void
    {
        $exception = new InvalidRequestException('Custom message', 422);

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(422, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new InvalidRequestException('Custom message', InvalidRequestException::ERROR_INVALID_REQUEST_LINE, $previous);

        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(InvalidRequestException::ERROR_INVALID_REQUEST_LINE, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionExtendsHttpProtocolException(): void
    {
        $exception = new InvalidRequestException();

        $this->assertInstanceOf(HttpProtocolException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
