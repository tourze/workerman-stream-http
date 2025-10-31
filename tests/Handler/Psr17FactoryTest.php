<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Handler;

use Nyholm\Psr7\Factory\Psr17Factory as NyholmPsr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Handler\Psr17Factory;

/**
 * @internal
 */
#[CoversClass(Psr17Factory::class)]
final class Psr17FactoryTest extends TestCase
{
    public function testGetInstanceReturnsSameInstance(): void
    {
        $factory1 = Psr17Factory::getInstance();
        $factory2 = Psr17Factory::getInstance();

        $this->assertSame($factory1, $factory2);
        $this->assertInstanceOf(NyholmPsr17Factory::class, $factory1);
    }

    public function testGetInstanceReturnsNyholmPsr17Factory(): void
    {
        $factory = Psr17Factory::getInstance();

        $this->assertInstanceOf(NyholmPsr17Factory::class, $factory);
    }

    public function testFactoryCanCreateRequest(): void
    {
        $factory = Psr17Factory::getInstance();

        $request = $factory->createRequest('GET', 'https://example.com');

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('https://example.com', (string) $request->getUri());
    }

    public function testFactoryCanCreateResponse(): void
    {
        $factory = Psr17Factory::getInstance();

        $response = $factory->createResponse(200, 'OK');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
    }

    public function testFactoryCanCreateStream(): void
    {
        $factory = Psr17Factory::getInstance();

        $stream = $factory->createStream('test content');

        $this->assertEquals('test content', $stream->getContents());
    }
}
