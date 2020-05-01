<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Set as Structure;
use Innmind\BlackBox\{
    Set as DataSet,
    Random\RandomInt,
};
use Fixtures\Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testOf()
    {
        $this->assertInstanceOf(
            DataSet::class,
            Set::of('string', new DataSet\Chars)
        );
    }

    public function testGenerates100ValuesByDefault()
    {
        $sets = Set::of('string', new DataSet\Chars);

        $this->assertInstanceOf(\Generator::class, $sets->values(new RandomInt));
        $this->assertCount(100, \iterator_to_array($sets->values(new RandomInt)));

        foreach ($sets->values(new RandomInt) as $set) {
            $this->assertInstanceOf(DataSet\Value::class, $set);
            $this->assertInstanceOf(Structure::class, $set->unwrap());
            $this->assertSame('string', (string) $set->unwrap()->type());
        }
    }

    public function testGeneratesSequencesOfDifferentSizes()
    {
        $sets = Set::of(
            'string',
            new DataSet\Chars,
            DataSet\Integers::between(0, 50)
        );
        $sizes = [];

        foreach ($sets->values(new RandomInt) as $set) {
            $sizes[] = $set->unwrap()->size();
        }

        $this->assertTrue(\count(\array_unique($sizes)) > 1);
    }

    public function testTake()
    {
        $sets1 = Set::of('string', new DataSet\Chars);
        $sets2 = $sets1->take(50);

        $this->assertNotSame($sets1, $sets2);
        $this->assertInstanceOf(DataSet::class, $sets2);
        $this->assertCount(100, \iterator_to_array($sets1->values(new RandomInt)));
        $this->assertCount(50, \iterator_to_array($sets2->values(new RandomInt)));
    }

    public function testFilter()
    {
        $sets = Set::of('string', DataSet\Chars::any());
        $sets2 = $sets->filter(fn($set) => $set->size() % 2 === 0);

        $this->assertInstanceOf(DataSet::class, $sets2);
        $this->assertNotSame($sets, $sets2);

        $hasOddSet = fn(bool $hasOddSet, $set) => $hasOddSet || $set->unwrap()->size() % 2 === 1;

        $this->assertTrue(
            \array_reduce(
                \iterator_to_array($sets->values(new RandomInt)),
                $hasOddSet,
                false,
            ),
        );
        $this->assertFalse(
            \array_reduce(
                \iterator_to_array($sets2->values(new RandomInt)),
                $hasOddSet,
                false,
            ),
        );
    }

    public function testFlagStructureAsMutableWhenUnderlyingSetValuesAreMutable()
    {
        $sets = Set::of(
            'object',
            DataSet\Decorate::mutable(
                fn() => new \stdClass,
                new DataSet\Chars,
            ),
        );

        foreach ($sets->values(new RandomInt) as $set) {
            $this->assertFalse($set->isImmutable());
            $this->assertNotSame($set->unwrap(), $set->unwrap());
            $this->assertSame($set->unwrap()->size(), $set->unwrap()->size());
        }
    }

    public function testNonEmptySetCanBeShrunk()
    {
        $sets = Set::of('string', DataSet\Chars::any(), DataSet\Integers::between(1, 100));

        foreach ($sets->values(new RandomInt) as $value) {
            $this->assertTrue($value->shrinkable());
        }
    }

    public function testEmptySetCanNotBeShrunk()
    {
        $sets = Set::of('string', DataSet\Chars::any(), DataSet\Integers::below(1));

        foreach ($sets->values(new RandomInt) as $value) {
            if (!$value->unwrap()->empty()) {
                // as it can generate sets of 1 element
                continue;
            }

            $this->assertFalse($value->shrinkable());
        }
    }

    public function testNonEmptySetAreShrunkWithDifferentStrategies()
    {
        $sets = Set::of('string', DataSet\Chars::any(), DataSet\Integers::between(3, 100));

        foreach ($sets->values(new RandomInt) as $value) {
            if ($value->unwrap()->size() === 3) {
                // when generating the lower bound it will shrink identity values
                continue;
            }

            $dichotomy = $value->shrink();
            $this->assertFalse(
                $dichotomy->a()->unwrap()->equals($dichotomy->b()->unwrap()),
                "Initial set size: {$value->unwrap()->size()}",
            );
        }
    }

    public function testShrunkSetsDoContainsLessThanTheInitialValue()
    {
        $sets = Set::of('string', DataSet\Chars::any(), DataSet\Integers::between(2, 100));

        foreach ($sets->values(new RandomInt) as $value) {
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

    public function testShrinkingStrategyAReduceTheSetFasterThanStrategyB()
    {
        $sets = Set::of('string', DataSet\Chars::any(), DataSet\Integers::between(3, 100));

        foreach ($sets->values(new RandomInt) as $value) {
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
        $sets = Set::of('string', DataSet\Chars::any(), DataSet\Integers::between(1, 100));

        foreach ($sets->values(new RandomInt) as $value) {
            $dichotomy = $value->shrink();

            $this->assertTrue($dichotomy->a()->isImmutable());
            $this->assertTrue($dichotomy->b()->isImmutable());
        }

        $sets = Set::of(
            'object',
            DataSet\Decorate::mutable(
                fn() => new \stdClass,
                new DataSet\Chars,
            ),
            DataSet\Integers::between(1, 100),
        );

        foreach ($sets->values(new RandomInt) as $value) {
            $dichotomy = $value->shrink();

            $this->assertFalse($dichotomy->a()->isImmutable());
            $this->assertFalse($dichotomy->b()->isImmutable());
        }
    }
}
