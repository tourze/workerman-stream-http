<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Context;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Context\ContextMap;
use Tourze\Workerman\StreamHTTP\Context\HttpContext;

/**
 * @internal
 */
#[CoversClass(ContextMap::class)]
final class ContextMapTest extends TestCase
{
    public function testGetContextMapReturnsSameInstance(): void
    {
        $contextMap1 = ContextMap::getContextMap();
        $contextMap2 = ContextMap::getContextMap();

        $this->assertSame($contextMap1, $contextMap2);
        $this->assertInstanceOf(\WeakMap::class, $contextMap1);
    }

    public function testContextMapIsWeakMap(): void
    {
        $contextMap = ContextMap::getContextMap();

        $this->assertInstanceOf(\WeakMap::class, $contextMap);
    }

    public function testContextMapStoresObjectReferences(): void
    {
        $contextMap = ContextMap::getContextMap();

        $object = new \stdClass();
        $object->test = 'value';

        $httpContext = new HttpContext();
        $contextMap[$object] = $httpContext;

        $this->assertEquals($httpContext, $contextMap[$object]);

        // 当对象被垃圾回收时，WeakMap会自动清理
        unset($object);
        // 注意：在PHP中，WeakMap的清理是由垃圾回收器决定的
        // 所以我们不能直接测试清理是否发生
    }
}
