<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Sequence as Structure;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    use BlackBox;

    public function testOf()
    {
        $this->assertInstanceOf(
            Set::class,
            Sequence::of(
                Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
                Set\Integers::between(0, 1),
            ),
        );
    }

    public function testGenerate()
    {
        $this
            ->forAll(Sequence::of(
                Set\Chars::any(),
                Set\Integers::between(0, 5),
            ))
            ->then(function($sequence) {
                $this->assertInstanceOf(Structure::class, $sequence);
                $this->assertLessThanOrEqual(5, $sequence->size());
            });
    }
}
