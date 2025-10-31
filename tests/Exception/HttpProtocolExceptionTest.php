<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\StreamHTTP\Exception\HttpProtocolException;

/**
 * @internal
 */
#[CoversClass(HttpProtocolException::class)]
final class HttpProtocolExceptionTest extends AbstractExceptionTestCase
{
    public function testAbstractClass(): void
    {
        $this->assertTrue((new \ReflectionClass(HttpProtocolException::class))->isAbstract());
    }

    public function testExtendsRuntimeException(): void
    {
        $this->assertTrue((new \ReflectionClass(HttpProtocolException::class))->isSubclassOf(\RuntimeException::class));
    }

    public function testConstructorExists(): void
    {
        $reflection = new \ReflectionClass(HttpProtocolException::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());
    }
}
