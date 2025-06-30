<?php

namespace Tourze\Workerman\StreamHTTP\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;
use Tourze\Workerman\StreamHTTP\Exception\InvalidRequestException;

class InvalidRequestExceptionTest extends TestCase
{
    public function testDefaultException(): void
    {
        $exception = new InvalidRequestException();
        
        $this->assertSame('Invalid request', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithMessage(): void
    {
        $exception = new InvalidRequestException('Custom message');
        
        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
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
        $exception = new InvalidRequestException('Custom message', 400, $previous);
        
        $this->assertSame('Custom message', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionExtendsHttpProtocolException(): void
    {
        $exception = new InvalidRequestException();
        
        $this->assertInstanceOf(HttpProtocolException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}