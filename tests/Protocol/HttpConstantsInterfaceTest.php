<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Protocol\HttpConstantsInterface;

/**
 * @internal
 */
#[CoversClass(HttpConstantsInterface::class)]
class HttpConstantsInterfaceTest extends TestCase
{
    public function testConstants(): void
    {
        $this->assertSame("\r", HttpConstantsInterface::CR);
        $this->assertSame("\n", HttpConstantsInterface::LF);
        $this->assertSame("\r\n", HttpConstantsInterface::CRLF);
        $this->assertSame("\r\n\r\n", HttpConstantsInterface::CRLF2);
    }

    public function testCrlfCombination(): void
    {
        $this->assertSame(
            HttpConstantsInterface::CR . HttpConstantsInterface::LF,
            HttpConstantsInterface::CRLF
        );
    }

    public function testCrlf2Combination(): void
    {
        $this->assertSame(
            HttpConstantsInterface::CRLF . HttpConstantsInterface::CRLF,
            HttpConstantsInterface::CRLF2
        );
    }
}
