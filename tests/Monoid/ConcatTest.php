<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid\Concat,
    Monoid,
    Str,
};
use Innmind\BlackBox\PHPUnit\Framework\TestCase;

class ConcatTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Monoid::class, Concat::monoid);
    }

    public function testCombine()
    {
        $str = Concat::monoid->combine(Str::of('foo'), Str::of('bar'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame(
            'foobar',
            $str->toString(),
        );
    }
}
