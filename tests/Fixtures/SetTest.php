<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Set as Structure;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set as DataSet,
};
use Fixtures\Innmind\Immutable\Set;

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

    public function testGenerate(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of(
                DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
                DataSet\Integers::between(0, 5),
            ))
            ->prove(function($set) {
                $this->assertInstanceOf(Structure::class, $set);
                $this->assertLessThanOrEqual(5, $set->size());
            });
    }
}
