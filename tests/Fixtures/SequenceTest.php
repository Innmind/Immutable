<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Sequence as Structure;
use Innmind\BlackBox\Set;
use Fixtures\Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            Set::class,
            new Sequence('string', new Set\Chars)
        );
    }

    public function testOf()
    {
        $this->assertInstanceOf(
            Sequence::class,
            Sequence::of('string', new Set\Chars)
        );
    }

    public function testGenerates100ValuesByDefault()
    {
        $sequences = new Sequence('string', new Set\Chars);

        $this->assertInstanceOf(\Generator::class, $sequences->values());
        $this->assertCount(100, \iterator_to_array($sequences->values()));

        foreach ($sequences->values() as $sequence) {
            $this->assertInstanceOf(Set\Value::class, $sequence);
            $this->assertInstanceOf(Structure::class, $sequence->unwrap());
            $this->assertSame('string', (string) $sequence->unwrap()->type());
        }
    }

    public function testGeneratesSequencesOfDifferentSizes()
    {
        $sequences = new Sequence(
            'string',
            new Set\Chars,
            Set\Integers::between(0, 50)
        );
        $sizes = [];

        foreach ($sequences->values() as $sequence) {
            $sizes[] = $sequence->unwrap()->size();
        }

        $this->assertTrue(\count(\array_unique($sizes)) > 1);
    }

    public function testTake()
    {
        $sequences1 = new Sequence('string', new Set\Chars);
        $sequences2 = $sequences1->take(50);

        $this->assertNotSame($sequences1, $sequences2);
        $this->assertInstanceOf(Sequence::class, $sequences2);
        $this->assertCount(100, \iterator_to_array($sequences1->values()));
        $this->assertCount(50, \iterator_to_array($sequences2->values()));
    }

    public function testFilter()
    {
        $sequences = new Sequence('string', new Set\Chars);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Sequence set can\'t be filtered, underlying set must be filtered beforehand');

        $sequences->filter(static function($sequence): bool {
            return $sequence->size() % 2 === 0;
        });
    }

    public function testFlagStructureAsMutableWhenUnderlyingSetValuesAreMutable()
    {
        $sequences = new Sequence(
            'object',
            Set\Decorate::mutable(
                fn() => new \stdClass,
                new Set\Chars,
            ),
        );

        foreach ($sequences->values() as $sequence) {
            $this->assertFalse($sequence->isImmutable());
            $this->assertNotSame($sequence->unwrap(), $sequence->unwrap());
            $this->assertSame($sequence->unwrap()->size(), $sequence->unwrap()->size());
        }
    }

    public function testNonEmptySequenceCanBeShrunk()
    {
        $sequences = new Sequence('string', Set\Chars::any(), Set\Integers::between(1, 100));

        foreach ($sequences->values() as $value) {
            $this->assertTrue($value->shrinkable());
        }
    }

    public function testEmptySequenceCanNotBeShrunk()
    {
        $sequences = new Sequence('string', Set\Chars::any(), Set\Integers::below(1));

        foreach ($sequences->values() as $value) {
            if (!$value->unwrap()->empty()) {
                // as it can generate sequences of 1 element
                continue;
            }

            $this->assertFalse($value->shrinkable());
        }
    }

    public function testNonEmptySequenceAreShrunkWithDifferentStrategies()
    {
        $sequences = new Sequence('string', Set\Chars::any(), Set\Integers::between(3, 100));

        foreach ($sequences->values() as $value) {
            $dichotomy = $value->shrink();
            $this->assertFalse(
                $dichotomy->a()->unwrap()->equals($dichotomy->b()->unwrap()),
                "Initial sequence size: {$value->unwrap()->size()}",
            );
        }
    }

    public function testShrunkSequencesDoContainsLessThanTheInitialValue()
    {
        $sequences = new Sequence('string', Set\Chars::any(), Set\Integers::between(2, 100));

        foreach ($sequences->values() as $value) {
            $dichotomy = $value->shrink();

            $this->assertLessThan($value->unwrap()->size(), $dichotomy->a()->unwrap()->size());
            $this->assertLessThan($value->unwrap()->size(), $dichotomy->b()->unwrap()->size());
        }
    }

    public function testShrinkingStrategyAReduceTheSequenceFasterThanStrategyB()
    {
        $sequences = new Sequence('string', Set\Chars::any(), Set\Integers::between(3, 100));

        foreach ($sequences->values() as $value) {
            $dichotomy = $value->shrink();

            $this->assertLessThan($dichotomy->b()->unwrap()->size(), $dichotomy->a()->unwrap()->size());
        }
    }

    public function testShrunkValuesConserveMutabilityProperty()
    {
        $sequences = new Sequence('string', Set\Chars::any(), Set\Integers::between(1, 100));

        foreach ($sequences->values() as $value) {
            $dichotomy = $value->shrink();

            $this->assertTrue($dichotomy->a()->isImmutable());
            $this->assertTrue($dichotomy->b()->isImmutable());
        }

        $sequences = new Sequence(
            'object',
            Set\Decorate::mutable(
                fn() => new \stdClass,
                new Set\Chars,
            ),
            Set\Integers::between(1, 100),
        );

        foreach ($sequences->values() as $value) {
            $dichotomy = $value->shrink();

            $this->assertFalse($dichotomy->a()->isImmutable());
            $this->assertFalse($dichotomy->b()->isImmutable());
        }
    }
}
