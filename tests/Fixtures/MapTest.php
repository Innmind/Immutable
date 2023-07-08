<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Map as Structure;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    use BlackBox;

    public function testOf()
    {
        $this->assertInstanceOf(
            Set::class,
            Map::of(
                Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
                Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
            ),
        );
    }

    public function testGenerate()
    {
        $this
            ->forAll(Map::of(
                Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
                Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
                Set\Integers::between(0, 5),
            ))
            ->then(function($map) {
                $this->assertInstanceOf(Structure::class, $map);
                $this->assertLessThanOrEqual(5, $map->size());
            });
    }
}
