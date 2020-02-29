<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Set as Structure;
use Innmind\BlackBox\Set as DataSet;
use Fixtures\Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;

class SetTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            DataSet::class,
            new Set('string', new DataSet\Chars)
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Set::class,
            Set::of('string', new DataSet\Chars)
        );
    }

    public function testGenerates100ValuesByDefault()
    {
        $sets = new Set('string', new DataSet\Chars);

        $this->assertInstanceOf(\Generator::class, $sets->values());
        $this->assertCount(100, \iterator_to_array($sets->values()));

        foreach ($sets->values() as $set) {
            $this->assertInstanceOf(DataSet\Value::class, $set);
            $this->assertInstanceOf(Structure::class, $set->unwrap());
            $this->assertSame('string', (string) $set->unwrap()->type());
        }
    }

    public function testGeneratesSequencesOfDifferentSizes()
    {
        $sets = new Set(
            'string',
            new DataSet\Chars,
            DataSet\Integers::between(0, 50)
        );
        $sizes = [];

        foreach ($sets->values() as $set) {
            $sizes[] = $set->unwrap()->size();
        }

        $this->assertTrue(\count(\array_unique($sizes)) > 1);
    }

    public function testTake()
    {
        $sets1 = new Set('string', new DataSet\Chars);
        $sets2 = $sets1->take(50);

        $this->assertNotSame($sets1, $sets2);
        $this->assertInstanceOf(Set::class, $sets2);
        $this->assertCount(100, \iterator_to_array($sets1->values()));
        $this->assertCount(50, \iterator_to_array($sets2->values()));
    }

    public function testFilter()
    {
        $sets = new Set('string', new DataSet\Chars);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Set set can\'t be filtered, underlying set must be filtered beforehand');

        $sets->filter(static function($set): bool {
            return $set->size() % 2 === 0;
        });
    }

    public function testFlagStructureAsMutableWhenUnderlyingSetValuesAreMutable()
    {
        $sets = new Set(
            'object',
            DataSet\Decorate::mutable(
                fn() => new \stdClass,
                new DataSet\Chars,
            ),
        );

        foreach ($sets->values() as $set) {
            $this->assertFalse($set->isImmutable());
            $this->assertNotSame($set->unwrap(), $set->unwrap());
            $this->assertSame($set->unwrap()->size(), $set->unwrap()->size());
        }
    }

    public function testNonEmptySetCanBeShrunk()
    {
        $sets = new Set('string', DataSet\Chars::any(), DataSet\Integers::above(1));

        foreach ($sets->values() as $value) {
            $this->assertTrue($value->shrinkable());
        }
    }

    public function testEmptySetCanNotBeShrunk()
    {
        $sets = new Set('string', DataSet\Chars::any(), DataSet\Integers::below(1));

        foreach ($sets->values() as $value) {
            if (!$value->unwrap()->empty()) {
                // as it can generate sets of 1 element
                continue;
            }

            $this->assertFalse($value->shrinkable());
        }
    }

    public function testNonEmptySetAreShrunkWithDifferentStrategies()
    {
        $sets = new Set('string', DataSet\Chars::any(), DataSet\Integers::above(1));

        foreach ($sets->values() as $value) {
            $dichotomy = $value->shrink();
            $this->assertFalse($dichotomy->a()->unwrap()->equals($dichotomy->b()->unwrap()));
        }
    }

    public function testShrunkSetsDoContainsLessThanTheInitialValue()
    {
        $sets = new Set('string', DataSet\Chars::any(), DataSet\Integers::above(1));

        foreach ($sets->values() as $value) {
            $dichotomy = $value->shrink();

            $this->assertLessThan($value->unwrap()->size(), $dichotomy->a()->unwrap()->size());
            $this->assertLessThan($value->unwrap()->size(), $dichotomy->b()->unwrap()->size());
        }
    }

    public function testShrinkingStrategyAReduceTheSetFasterThanStrategyB()
    {
        $sets = new Set('string', DataSet\Chars::any(), DataSet\Integers::above(1));

        foreach ($sets->values() as $value) {
            $dichotomy = $value->shrink();

            $this->assertLessThan($dichotomy->b()->unwrap()->size(), $dichotomy->a()->unwrap()->size());
        }
    }

    public function testShrunkValuesConserveMutabilityProperty()
    {
        $sets = new Set('string', DataSet\Chars::any(), DataSet\Integers::above(1));

        foreach ($sets->values() as $value) {
            $dichotomy = $value->shrink();

            $this->assertTrue($dichotomy->a()->isImmutable());
            $this->assertTrue($dichotomy->b()->isImmutable());
        }

        $sets = new Set(
            'object',
            DataSet\Decorate::mutable(
                fn() => new \stdClass,
                new DataSet\Chars,
            ),
            DataSet\Integers::above(1),
        );

        foreach ($sets->values() as $value) {
            $dichotomy = $value->shrink();

            $this->assertFalse($dichotomy->a()->isImmutable());
            $this->assertFalse($dichotomy->b()->isImmutable());
        }
    }
}
