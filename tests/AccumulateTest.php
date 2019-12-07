<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\Accumulate;
use PHPUnit\Framework\TestCase;

class AccumulateTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            \Iterator::class,
            new Accumulate((function() {
                yield 1;
            })()),
        );

        $loaded = false;
        $iterator = new Accumulate((function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            $loaded = true;
        })());

        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3], \iterator_to_array($iterator));
        $this->assertTrue($loaded);
        $this->assertSame([1, 2, 3], \iterator_to_array($iterator));
    }

    public function testSupportsPartialIterations()
    {
        $loaded = false;
        $iterator = new Accumulate((function() use (&$loaded) {
            yield 1;
            yield 2;
            yield 3;
            yield 4;
            $loaded = true;
        })());

        $this->assertSame(0, $iterator->key());
        $this->assertSame(1, $iterator->current());
        $this->assertNull($iterator->next());
        $this->assertSame(1, $iterator->key());
        $this->assertSame(2, $iterator->current());
        $this->assertNull($iterator->rewind());
        $this->assertFalse($loaded);
        $this->assertSame([1, 2, 3, 4], \iterator_to_array($iterator));
    }
}
