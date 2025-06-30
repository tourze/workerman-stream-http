<?php

namespace Tourze\Workerman\StreamHTTP\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;

class HttpProtocolExceptionTest extends TestCase
{
    public function testDefaultException(): void
    {
        $exception = new HttpProtocolException();
        
        $this->assertSame('', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithMessage(): void
    {
        $exception = new HttpProtocolException('Test message');
        
        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
    }

    public function testExceptionWithCustomCode(): void
    {
        $exception = new HttpProtocolException('Test message', 500);
        
        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new HttpProtocolException('Test message', 400, $previous);
        
        $this->assertSame('Test message', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testExceptionIsRuntimeException(): void
    {
        $exception = new HttpProtocolException();
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}