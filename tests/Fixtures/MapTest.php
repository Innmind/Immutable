<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Map as Structure;
use Innmind\BlackBox\Set;
use Fixtures\Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Set::class,
            new Map(
                'string',
                'string',
                new Set\Chars,
                new Set\Chars
            )
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Map::class,
            Map::of(
                'string',
                'string',
                new Set\Chars,
                new Set\Chars
            )
        );
    }

    public function testGenerates100ValuesByDefault()
    {
        $maps = new Map(
            'string',
            'int',
            new Set\Chars,
            Set\Integers::any()
        );

        $this->assertInstanceOf(\Generator::class, $maps->values());
        $this->assertCount(100, \iterator_to_array($maps->values()));

        foreach ($maps->values() as $map) {
            $this->assertInstanceOf(Structure::class, $map);
            $this->assertSame('string', (string) $map->keyType());
            $this->assertSame('int', (string) $map->valueType());
        }
    }

    public function testGeneratesSequencesOfDifferentSizes()
    {
        $maps = new Map(
            'string',
            'string',
            new Set\Chars,
            new Set\Chars,
            Set\Integers::between(0, 50)
        );
        $sizes = [];

        foreach ($maps->values() as $map) {
            $sizes[] = $map->size();
        }

        $this->assertTrue(\count(\array_unique($sizes)) > 1);
    }

    public function testTake()
    {
        $maps1 = new Map(
            'string',
            'string',
            new Set\Chars,
            new Set\Chars
        );
        $maps2 = $maps1->take(50);

        $this->assertNotSame($maps1, $maps2);
        $this->assertInstanceOf(Map::class, $maps2);
        $this->assertCount(100, \iterator_to_array($maps1->values()));
        $this->assertCount(50, \iterator_to_array($maps2->values()));
    }

    public function testFilter()
    {
        $maps1 = new Map(
            'string',
            'string',
            new Set\Chars,
            new Set\Chars
        );
        $maps2 = $maps1->filter(static function($map): bool {
            return $map->size() % 2 === 0;
        });

        $this->assertNotSame($maps1, $maps2);
        $this->assertInstanceOf(Map::class, $maps2);

        $values1 = \iterator_to_array($maps1->values());
        $values2 = \iterator_to_array($maps2->values());
        $values1 = \array_map(function($map) {
            return $map->size() % 2;
        }, $values1);
        $values2 = \array_map(function($map) {
            return $map->size() % 2;
        }, $values2);
        $values1 = \array_unique($values1);
        $values2 = \array_unique($values2);
        \sort($values1);

        $this->assertSame([0, 1], \array_values($values1));
        $this->assertSame([0], \array_values($values2));
    }
}
