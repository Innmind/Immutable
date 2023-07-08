<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid\Concat,
    Monoid,
    Str,
};
use PHPUnit\Framework\TestCase;

class ConcatTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(Monoid::class, new Concat);
    }

    public function testCombine()
    {
        $str = (new Concat)->combine(Str::of('foo'), Str::of('bar'));

        $this->assertInstanceOf(Str::class, $str);
        $this->assertSame(
            'foobar',
            $str->toString(),
        );
    }
}
