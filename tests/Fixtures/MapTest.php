<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Map as Structure;
use Innmind\BlackBox\{
    Set,
    Random\RandomInt,
};
use Fixtures\Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class MapTest extends TestCase
{
    public function testOf()
    {
        $this->assertInstanceOf(
            Set::class,
            Map::of(
                'string',
                'string',
                Set\Chars::any(),
                Set\Chars::any()
            )
        );
    }

    public function testGeneratesAtMost100ValuesByDefault()
    {
        $maps = Map::of(
            'string',
            'int',
            Set\Chars::any(),
            Set\Integers::any()
        );

        $this->assertInstanceOf(\Generator::class, $maps->values(new RandomInt));
        $count = \count(\iterator_to_array($maps->values(new RandomInt)));
        $this->assertLessThanOrEqual(100, $count);
        $this->assertGreaterThan(10, $count);

        foreach ($maps->values(new RandomInt) as $map) {
            $this->assertInstanceOf(Set\Value::class, $map);
            $this->assertInstanceOf(Structure::class, $map->unwrap());
            $this->assertSame('string', (string) $map->unwrap()->keyType());
            $this->assertSame('int', (string) $map->unwrap()->valueType());
        }
    }

    public function testGeneratesMapsOfDifferentSizes()
    {
        $maps = Map::of(
            'string',
            'string',
            Set\Chars::any(),
            Set\Chars::any(),
            Set\Integers::between(0, 50)
        );
        $sizes = [];

        foreach ($maps->values(new RandomInt) as $map) {
            $sizes[] = $map->unwrap()->size();
        }

        $this->assertTrue(\count(\array_unique($sizes)) > 1);
    }

    public function testTake()
    {
        $maps1 = Map::of(
            'string',
            'string',
            Set\Chars::any(),
            Set\Chars::any()
        );
        $maps2 = $maps1->take(50);

        $this->assertNotSame($maps1, $maps2);
        $this->assertInstanceOf(Set::class, $maps2);
        $count1 = \count(\iterator_to_array($maps1->values(new RandomInt)));
        $count2 = \count(\iterator_to_array($maps2->values(new RandomInt)));
        $this->assertLessThanOrEqual(100, $count1);
        $this->assertLessThanOrEqual(50, $count2);
        $this->assertGreaterThan($count2, $count1);
    }

    public function testFilter()
    {
        $maps = Map::of(
            'string',
            'string',
            Set\Chars::any(),
            Set\Chars::any(),
        );
        $maps2 = $maps->filter(fn($map) => $map->size() % 2 === 0);

        $this->assertInstanceOf(Set::class, $maps2);
        $this->assertNotSame($maps, $maps2);

        $hasOddMap = fn(bool $hasOddMap, $map) => $hasOddMap || $map->unwrap()->size() % 2 === 1;

        $this->assertTrue(
            \array_reduce(
                \iterator_to_array($maps->values(new RandomInt)),
                $hasOddMap,
                false,
            ),
        );
        $this->assertFalse(
            \array_reduce(
                \iterator_to_array($maps2->values(new RandomInt)),
                $hasOddMap,
                false,
            ),
        );
    }

    public function testFlagStructureAsMutableWhenUnderlyingKeysAreMutable()
    {
        $maps = Map::of(
            'object',
            'string',
            Set\Decorate::mutable(
                fn() => new \stdClass,
                Set\Chars::any(),
            ),
            Set\Chars::any(),
        );

        foreach ($maps->values(new RandomInt) as $map) {
            $this->assertFalse($map->isImmutable());
            $this->assertNotSame($map->unwrap(), $map->unwrap());
            $this->assertSame($map->unwrap()->size(), $map->unwrap()->size());
        }
    }

    public function testFlagStructureAsMutableWhenUnderlyingValuesAreMutable()
    {
        $maps = Map::of(
            'string',
            'object',
            Set\Chars::any(),
            Set\Decorate::mutable(
                fn() => new \stdClass,
                Set\Chars::any(),
            ),
        );

        foreach ($maps->values(new RandomInt) as $map) {
            $this->assertFalse($map->isImmutable());
            $this->assertNotSame($map->unwrap(), $map->unwrap());
        }
    }

    public function testNonEmptyMapCanBeShrunk()
    {
        $maps = Map::of(
            'string',
            'string',
            Set\Chars::any(),
            Set\Chars::any(),
            Set\Integers::between(1, 100),
        );

        foreach ($maps->values(new RandomInt) as $value) {
            $this->assertTrue($value->shrinkable());
        }
    }

    public function testEmptyMapCanNotBeShrunk()
    {
        $maps = Map::of(
            'string',
            'string',
            Set\Chars::any(),
            Set\Chars::any(),
            Set\Integers::below(1),
        );

        foreach ($maps->values(new RandomInt) as $value) {
            if (!$value->unwrap()->empty()) {
                // as it can generate maps of 1 element
                continue;
            }

            $this->assertFalse($value->shrinkable());
        }
    }

    public function testNonEmptyMapAreShrunkWithDifferentStrategies()
    {
        $maps = Map::of(
            'string',
            'string',
            Set\Chars::any(),
            Set\Chars::any(),
            Set\Integers::between(3, 100),
        );

        foreach ($maps->values(new RandomInt) as $value) {
            if ($value->unwrap()->size() < 4) {
                // when generating the lower bound it will shrink identity values
                continue;
            }

            $dichotomy = $value->shrink();
            $this->assertFalse($dichotomy->a()->unwrap()->equals($dichotomy->b()->unwrap()));
        }
    }

    public function testShrunkMapsDoContainsLessThanTheInitialValue()
    {
        $maps = Map::of(
            'string',
            'string',
            Set\Chars::any(),
            Set\Chars::any(),
            Set\Integers::between(2, 100),
        );

        foreach ($maps->values(new RandomInt) as $value) {
            if ($value->unwrap()->size() < 4) {
                // otherwise strategy A will return it's identity since 3/2 won't
                // match the predicate of minimum size 2, so strategy will return
                // an identity value
                continue;
            }

            $dichotomy = $value->shrink();

            $this->assertLessThan($value->unwrap()->size(), $dichotomy->a()->unwrap()->size());
            $this->assertLessThan($value->unwrap()->size(), $dichotomy->b()->unwrap()->size());
        }
    }

    public function testShrinkingStrategyAReduceTheMapFasterThanStrategyB()
    {
        $maps = Map::of(
            'string',
            'string',
            Set\Chars::any(),
            Set\Chars::any(),
            Set\Integers::between(3, 100),
        );

        foreach ($maps->values(new RandomInt) as $value) {
            if ($value->unwrap()->size() < 6) {
                // otherwise strategy A will return it's identity since 5/2 won't
                // match the predicate of minimum size 3, so strategy will return
                // an identity value so it will always be greater than stragey B
                continue;
            }

            $dichotomy = $value->shrink();

            $this->assertLessThan($dichotomy->b()->unwrap()->size(), $dichotomy->a()->unwrap()->size());
        }
    }

    public function testShrunkValuesConserveMutabilityProperty()
    {
        $maps = Map::of(
            'string',
            'string',
            Set\Chars::any(),
            Set\Chars::any(),
            Set\Integers::between(1, 100),
        );

        foreach ($maps->values(new RandomInt) as $value) {
            $dichotomy = $value->shrink();

            $this->assertTrue($dichotomy->a()->isImmutable());
            $this->assertTrue($dichotomy->b()->isImmutable());
        }

        $maps = Map::of(
            'string',
            'object',
            Set\Chars::any(),
            Set\Decorate::mutable(
                fn() => new \stdClass,
                Set\Chars::any(),
            ),
            Set\Integers::between(1, 100),
        );

        foreach ($maps->values(new RandomInt) as $value) {
            $dichotomy = $value->shrink();

            $this->assertFalse($dichotomy->a()->isImmutable());
            $this->assertFalse($dichotomy->b()->isImmutable());
        }
    }
}
