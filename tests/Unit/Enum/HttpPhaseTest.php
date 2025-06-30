<?php

namespace Tourze\Workerman\StreamHTTP\Tests\Unit\Enum;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\StreamHTTP\Enum\HttpPhase;

class HttpPhaseTest extends TestCase
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

    public function testToSelectItem(): void
    {
        $item = HttpPhase::REQUEST_LINE->toSelectItem();
        $this->assertSame('Request Line', $item['label']);
        $this->assertSame('Request Line', $item['text']);
        $this->assertSame('Request Line', $item['name']);
        
        $item = HttpPhase::HEADERS->toSelectItem();
        $this->assertSame('Headers', $item['label']);
        $this->assertSame('Headers', $item['text']);
        $this->assertSame('Headers', $item['name']);
    }

    public function testToArray(): void
    {
        $this->assertSame(['value' => null, 'label' => 'Request Line'], HttpPhase::REQUEST_LINE->toArray());
        $this->assertSame(['value' => null, 'label' => 'Headers'], HttpPhase::HEADERS->toArray());
        $this->assertSame(['value' => null, 'label' => 'Body'], HttpPhase::BODY->toArray());
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