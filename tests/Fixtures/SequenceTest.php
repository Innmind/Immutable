<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Sequence as Structure;
use Innmind\BlackBox\{
    Set,
    Random\RandomInt,
};
use Fixtures\Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function testOf()
    {
        $this->assertInstanceOf(
            Set::class,
            Sequence::of('string', new Set\Chars)
        );
    }

    public function testGenerates100ValuesByDefault()
    {
        $sequences = Sequence::of('string', new Set\Chars);

        $this->assertInstanceOf(\Generator::class, $sequences->values(new RandomInt));
        $this->assertCount(100, \iterator_to_array($sequences->values(new RandomInt)));

        foreach ($sequences->values(new RandomInt) as $sequence) {
            $this->assertInstanceOf(Set\Value::class, $sequence);
            $this->assertInstanceOf(Structure::class, $sequence->unwrap());
            $this->assertSame('string', (string) $sequence->unwrap()->type());
        }
    }

    public function testGeneratesSequencesOfDifferentSizes()
    {
        $sequences = Sequence::of(
            'string',
            new Set\Chars,
            Set\Integers::between(0, 50)
        );
        $sizes = [];

        foreach ($sequences->values(new RandomInt) as $sequence) {
            $sizes[] = $sequence->unwrap()->size();
        }

        $this->assertTrue(\count(\array_unique($sizes)) > 1);
    }

    public function testTake()
    {
        $sequences1 = Sequence::of('string', new Set\Chars);
        $sequences2 = $sequences1->take(50);

        $this->assertNotSame($sequences1, $sequences2);
        $this->assertInstanceOf(Set::class, $sequences2);
        $this->assertCount(100, \iterator_to_array($sequences1->values(new RandomInt)));
        $this->assertCount(50, \iterator_to_array($sequences2->values(new RandomInt)));
    }

    public function testFilter()
    {
        $sequences = Sequence::of('string', Set\Chars::any());
        $sequences2 = $sequences->filter(fn($sequence) => $sequence->size() % 2 === 0);

        $this->assertInstanceOf(Set::class, $sequences2);
        $this->assertNotSame($sequences, $sequences2);

        $hasOddSequence = fn(bool $hasOddSequence, $sequence) => $hasOddSequence || $sequence->unwrap()->size() % 2 === 1;

        $this->assertTrue(
            \array_reduce(
                \iterator_to_array($sequences->values(new RandomInt)),
                $hasOddSequence,
                false,
            ),
        );
        $this->assertFalse(
            \array_reduce(
                \iterator_to_array($sequences2->values(new RandomInt)),
                $hasOddSequence,
                false,
            ),
        );
    }

    public function testFlagStructureAsMutableWhenUnderlyingSetValuesAreMutable()
    {
        $sequences = Sequence::of(
            'object',
            Set\Decorate::mutable(
                fn() => new \stdClass,
                new Set\Chars,
            ),
        );

        foreach ($sequences->values(new RandomInt) as $sequence) {
            $this->assertFalse($sequence->isImmutable());
            $this->assertNotSame($sequence->unwrap(), $sequence->unwrap());
            $this->assertSame($sequence->unwrap()->size(), $sequence->unwrap()->size());
        }
    }

    public function testNonEmptySequenceCanBeShrunk()
    {
        $sequences = Sequence::of('string', Set\Chars::any(), Set\Integers::between(1, 100));

        foreach ($sequences->values(new RandomInt) as $value) {
            $this->assertTrue($value->shrinkable());
        }
    }

    public function testEmptySequenceCanNotBeShrunk()
    {
        $sequences = Sequence::of('string', Set\Chars::any(), Set\Integers::below(1));

        foreach ($sequences->values(new RandomInt) as $value) {
            if (!$value->unwrap()->empty()) {
                // as it can generate sequences of 1 element
                continue;
            }

            $this->assertFalse($value->shrinkable());
        }
    }

    public function testNonEmptySequenceAreShrunkWithDifferentStrategies()
    {
        $sequences = Sequence::of('string', Set\Chars::any(), Set\Integers::between(3, 100));

        foreach ($sequences->values(new RandomInt) as $value) {
            if ($value->unwrap()->size() < 6) {
                // when generating the lower bound it will shrink identity values
                continue;
            }

            $dichotomy = $value->shrink();
            $this->assertFalse(
                $dichotomy->a()->unwrap()->equals($dichotomy->b()->unwrap()),
                "Initial sequence size: {$value->unwrap()->size()}",
            );
        }
    }

    public function testShrunkSequencesDoContainsLessThanTheInitialValue()
    {
        $sequences = Sequence::of('string', Set\Chars::any(), Set\Integers::between(2, 100));

        foreach ($sequences->values(new RandomInt) as $value) {
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

    public function testShrinkingStrategyAReduceTheSequenceFasterThanStrategyB()
    {
        $sequences = Sequence::of('string', Set\Chars::any(), Set\Integers::between(3, 100));

        foreach ($sequences->values(new RandomInt) as $value) {
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
        $sequences = Sequence::of('string', Set\Chars::any(), Set\Integers::between(1, 100));

        foreach ($sequences->values(new RandomInt) as $value) {
            $dichotomy = $value->shrink();

            $this->assertTrue($dichotomy->a()->isImmutable());
            $this->assertTrue($dichotomy->b()->isImmutable());
        }

        $sequences = Sequence::of(
            'object',
            Set\Decorate::mutable(
                fn() => new \stdClass,
                new Set\Chars,
            ),
            Set\Integers::between(1, 100),
        );

        foreach ($sequences->values(new RandomInt) as $value) {
            $dichotomy = $value->shrink();

            $this->assertFalse($dichotomy->a()->isImmutable());
            $this->assertFalse($dichotomy->b()->isImmutable());
        }
    }
}
