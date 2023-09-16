<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\Pair;
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class PairTest extends TestCase
{
    public function testInterface()
    {
        $pair = new Pair($key = new \stdClass, 42);

        $this->assertSame($key, $pair->key());
        $this->assertSame(42, $pair->value());
    }
}
