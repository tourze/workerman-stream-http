<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\StreamHTTP\Exception\ContextNotInitializedException;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;

/**
 * @internal
 */
#[CoversClass(ContextNotInitializedException::class)]
class ContextNotInitializedExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return ContextNotInitializedException::class;
    }

    public function testExceptionExtendsHttpProtocolException(): void
    {
        $exception = new ContextNotInitializedException();
        $this->assertInstanceOf(HttpProtocolException::class, $exception);
    }
}
