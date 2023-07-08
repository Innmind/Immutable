<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Set as Structure;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set as DataSet,
};
use Fixtures\Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    use BlackBox;

    public function testOf()
    {
        $this->assertInstanceOf(
            DataSet::class,
            Set::of(
                DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
                DataSet\Integers::between(0, 1),
            ),
        );
    }

    public function testGenerate()
    {
        $this
            ->forAll(Set::of(
                DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
                DataSet\Integers::between(0, 5),
            ))
            ->then(function($set) {
                $this->assertInstanceOf(Structure::class, $set);
                $this->assertLessThanOrEqual(5, $set->size());
            });
    }
}
