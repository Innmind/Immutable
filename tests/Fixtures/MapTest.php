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
            $this->assertInstanceOf(Set\Value::class, $map);
            $this->assertInstanceOf(Structure::class, $map->unwrap());
            $this->assertSame('string', (string) $map->unwrap()->keyType());
            $this->assertSame('int', (string) $map->unwrap()->valueType());
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
            $sizes[] = $map->unwrap()->size();
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
        $maps = new Map(
            'string',
            'string',
            new Set\Chars,
            new Set\Chars
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Map set can\'t be filtered, underlying sets must be filtered beforehand');

        $maps->filter(static function($map): bool {
            return $map->size() % 2 === 0;
        });
    }

    public function testFlagStructureAsMutableWhenUnderlyingKeysAreMutable()
    {
        $maps = new Map(
            'object',
            'string',
            Set\Decorate::mutable(
                fn() => new \stdClass,
                new Set\Chars,
            ),
            new Set\Chars,
        );

        foreach ($maps->values() as $map) {
            $this->assertFalse($map->isImmutable());
            $this->assertNotSame($map->unwrap(), $map->unwrap());
            $this->assertSame($map->unwrap()->size(), $map->unwrap()->size());
        }
    }

    public function testFlagStructureAsMutableWhenUnderlyingValuesAreMutable()
    {
        $maps = new Map(
            'string',
            'object',
            new Set\Chars,
            Set\Decorate::mutable(
                fn() => new \stdClass,
                new Set\Chars,
            ),
        );

        foreach ($maps->values() as $map) {
            $this->assertFalse($map->isImmutable());
            $this->assertNotSame($map->unwrap(), $map->unwrap());
        }
    }
}
