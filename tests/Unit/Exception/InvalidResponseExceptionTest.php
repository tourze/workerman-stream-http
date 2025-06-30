<?php

namespace Tourze\Workerman\StreamHTTP\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;
use Tourze\Workerman\StreamHTTP\Exception\InvalidResponseException;

class InvalidResponseExceptionTest extends TestCase
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