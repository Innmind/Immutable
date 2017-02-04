<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Pair,
    Symbol
};

class PairTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $p = new Pair($s = new Symbol('foo'), 42);

        $this->assertSame($s, $p->key());
        $this->assertSame(42, $p->value());
    }
}
