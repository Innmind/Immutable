<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Set as Structure;
use Innmind\BlackBox\{
    Set as DataSet,
    Random,
};
use Fixtures\Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
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

    public function testGeneratesAtMost100ValuesByDefault()
    {
        $sets = Set::of(
            DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
            DataSet\Integers::between(1, 10),
        );

        $this->assertInstanceOf(\Generator::class, $sets->values(Random::default));
        $count = \count(\iterator_to_array($sets->values(Random::default)));
        $this->assertLessThanOrEqual(100, $count);
        $this->assertGreaterThan(10, $count);

        foreach ($sets->values(Random::default) as $set) {
            $this->assertInstanceOf(DataSet\Value::class, $set);
            $this->assertInstanceOf(Structure::class, $set->unwrap());
        }
    }

    public function testGeneratesSequencesOfDifferentSizes()
    {
        $sets = Set::of(
            DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
            DataSet\Integers::between(0, 50),
        );
        $sizes = [];

        foreach ($sets->values(Random::default) as $set) {
            $sizes[] = $set->unwrap()->size();
        }

        $this->assertTrue(\count(\array_unique($sizes)) > 1);
    }

    public function testTake()
    {
        $sets1 = Set::of(
            DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
            DataSet\Integers::between(0, 1),
        );
        $sets2 = $sets1->take(50);

        $this->assertNotSame($sets1, $sets2);
        $this->assertInstanceOf(DataSet::class, $sets2);
        $count1 = \count(\iterator_to_array($sets1->values(Random::default)));
        $count2 = \count(\iterator_to_array($sets2->values(Random::default)));
        $this->assertLessThanOrEqual(100, $count1);
        $this->assertLessThanOrEqual(50, $count2);
        $this->assertGreaterThan($count2, $count1);
    }

    public function testFilter()
    {
        $sets = Set::of(
            DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
            DataSet\Integers::between(1, 10),
        );
        $sets2 = $sets->filter(static fn($set) => $set->size() % 2 === 0);

        $this->assertInstanceOf(DataSet::class, $sets2);
        $this->assertNotSame($sets, $sets2);

        $hasOddSet = static fn(bool $hasOddSet, $set) => $hasOddSet || $set->unwrap()->size() % 2 === 1;

        $this->assertTrue(
            \array_reduce(
                \iterator_to_array($sets->values(Random::default)),
                $hasOddSet,
                false,
            ),
        );
        $this->assertFalse(
            \array_reduce(
                \iterator_to_array($sets2->values(Random::default)),
                $hasOddSet,
                false,
            ),
        );
    }

    public function testFlagStructureAsMutableWhenUnderlyingSetValuesAreMutable()
    {
        $sets = Set::of(
            DataSet\Decorate::mutable(
                static fn() => new \stdClass,
                DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
            ),
        );

        foreach ($sets->values(Random::default) as $set) {
            $this->assertFalse($set->isImmutable());
            $this->assertNotSame($set->unwrap(), $set->unwrap());
            $this->assertSame($set->unwrap()->size(), $set->unwrap()->size());
        }
    }

    public function testNonEmptySetCanBeShrunk()
    {
        $sets = Set::of(
            DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
            DataSet\Integers::between(1, 10),
        );

        foreach ($sets->values(Random::default) as $value) {
            $this->assertTrue($value->shrinkable());
        }
    }

    public function testEmptySetCanNotBeShrunk()
    {
        $sets = Set::of(
            DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
            DataSet\Integers::below(1),
        );

        foreach ($sets->values(Random::default) as $value) {
            if (!$value->unwrap()->empty()) {
                // as it can generate sets of 1 element
                continue;
            }

            $this->assertFalse($value->shrinkable());
        }
    }

    public function testShrunkValuesConserveMutabilityProperty()
    {
        $sets = Set::of(
            DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
            DataSet\Integers::between(1, 100),
        );

        foreach ($sets->values(Random::default) as $value) {
            $dichotomy = $value->shrink();

            $this->assertTrue($dichotomy->a()->isImmutable());
            $this->assertTrue($dichotomy->b()->isImmutable());
        }

        $sets = Set::of(
            DataSet\Decorate::mutable(
                static fn() => new \stdClass,
                DataSet\Strings::madeOf(DataSet\Chars::any())->between(1, 2),
            ),
            DataSet\Integers::between(1, 100),
        );

        foreach ($sets->values(Random::default) as $value) {
            $dichotomy = $value->shrink();

            $this->assertFalse($dichotomy->a()->isImmutable());
            $this->assertFalse($dichotomy->b()->isImmutable());
        }
    }
}
