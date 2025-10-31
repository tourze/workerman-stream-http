<?php

declare(strict_types=1);

namespace Tourze\Workerman\StreamHTTP\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;

/**
 * @internal
 */
#[CoversClass(HttpPhase::class)]
final class HttpPhaseTest extends AbstractEnumTestCase
{
    public function testHttpPhaseValues(): void
    {
        $this->assertSame('REQUEST_LINE', HttpPhase::REQUEST_LINE->name);
        $this->assertSame('HEADERS', HttpPhase::HEADERS->name);
        $this->assertSame('BODY', HttpPhase::BODY->name);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Request Line', HttpPhase::REQUEST_LINE->getLabel());
        $this->assertSame('Headers', HttpPhase::HEADERS->getLabel());
        $this->assertSame('Body', HttpPhase::BODY->getLabel());
    }

    public function testToArray(): void
    {
        $this->assertSame(['value' => 'request_line', 'label' => 'Request Line'], HttpPhase::REQUEST_LINE->toArray());
        $this->assertSame(['value' => 'headers', 'label' => 'Headers'], HttpPhase::HEADERS->toArray());
        $this->assertSame(['value' => 'body', 'label' => 'Body'], HttpPhase::BODY->toArray());
    }

    public function testGenOptions(): void
    {
        $options = HttpPhase::genOptions();

        $this->assertCount(3, $options);
        $this->assertSame('Request Line', $options[0]['label']);
        $this->assertSame('Headers', $options[1]['label']);
        $this->assertSame('Body', $options[2]['label']);
    }
}
