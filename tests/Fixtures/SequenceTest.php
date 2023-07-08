<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Fixtures;

use Innmind\Immutable\Sequence as Structure;
use Innmind\BlackBox\{
    Set,
    Random,
};
use Fixtures\Innmind\Immutable\Sequence;
use PHPUnit\Framework\TestCase;

class SequenceTest extends TestCase
{
    public function testOf()
    {
        $this->assertInstanceOf(
            Set::class,
            Sequence::of(
                Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
                Set\Integers::between(0, 1),
            ),
        );
    }

    public function testGenerates100ValuesByDefault()
    {
        $sequences = Sequence::of(
            Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
            Set\Integers::between(0, 1),
        );

        $this->assertInstanceOf(\Generator::class, $sequences->values(Random::default));
        $this->assertCount(100, \iterator_to_array($sequences->values(Random::default)));

        foreach ($sequences->values(Random::default) as $sequence) {
            $this->assertInstanceOf(Set\Value::class, $sequence);
            $this->assertInstanceOf(Structure::class, $sequence->unwrap());
        }
    }

    public function testGeneratesSequencesOfDifferentSizes()
    {
        $sequences = Sequence::of(
            Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
            Set\Integers::between(0, 50),
        );
        $sizes = [];

        foreach ($sequences->values(Random::default) as $sequence) {
            $sizes[] = $sequence->unwrap()->size();
        }

        $this->assertTrue(\count(\array_unique($sizes)) > 1);
    }

    public function testTake()
    {
        $sequences1 = Sequence::of(
            Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
            Set\Integers::between(0, 1),
        );
        $sequences2 = $sequences1->take(50);

        $this->assertNotSame($sequences1, $sequences2);
        $this->assertInstanceOf(Set::class, $sequences2);
        $this->assertCount(100, \iterator_to_array($sequences1->values(Random::default)));
        $this->assertCount(50, \iterator_to_array($sequences2->values(Random::default)));
    }

    public function testFilter()
    {
        $sequences = Sequence::of(
            Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
            Set\Integers::between(1, 10),
        );
        $sequences2 = $sequences->filter(static fn($sequence) => $sequence->size() % 2 === 0);

        $this->assertInstanceOf(Set::class, $sequences2);
        $this->assertNotSame($sequences, $sequences2);

        $hasOddSequence = static fn(bool $hasOddSequence, $sequence) => $hasOddSequence || $sequence->unwrap()->size() % 2 === 1;

        $this->assertTrue(
            \array_reduce(
                \iterator_to_array($sequences->values(Random::default)),
                $hasOddSequence,
                false,
            ),
        );
        $this->assertFalse(
            \array_reduce(
                \iterator_to_array($sequences2->values(Random::default)),
                $hasOddSequence,
                false,
            ),
        );
    }

    public function testFlagStructureAsMutableWhenUnderlyingSetValuesAreMutable()
    {
        $sequences = Sequence::of(
            Set\Decorate::mutable(
                static fn() => new \stdClass,
                Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
            ),
            Set\Integers::between(1, 2),
        );

        foreach ($sequences->values(Random::default) as $sequence) {
            $this->assertFalse($sequence->isImmutable());
            $this->assertNotSame($sequence->unwrap(), $sequence->unwrap());
            $this->assertSame($sequence->unwrap()->size(), $sequence->unwrap()->size());
        }
    }

    public function testNonEmptySequenceCanBeShrunk()
    {
        $sequences = Sequence::of(
            Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
            Set\Integers::between(1, 100),
        );

        foreach ($sequences->values(Random::default) as $value) {
            $this->assertTrue($value->shrinkable());
        }
    }

    public function testEmptySequenceCanNotBeShrunk()
    {
        $sequences = Sequence::of(
            Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
            Set\Integers::below(1),
        );

        foreach ($sequences->values(Random::default) as $value) {
            if (!$value->unwrap()->empty()) {
                // as it can generate sequences of 1 element
                continue;
            }

            $this->assertFalse($value->shrinkable());
        }
    }

    public function testShrunkValuesConserveMutabilityProperty()
    {
        $sequences = Sequence::of(
            Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
            Set\Integers::between(1, 100),
        );

        foreach ($sequences->values(Random::default) as $value) {
            $dichotomy = $value->shrink();

            $this->assertTrue($dichotomy->a()->isImmutable());
            $this->assertTrue($dichotomy->b()->isImmutable());
        }

        $sequences = Sequence::of(
            Set\Decorate::mutable(
                static fn() => new \stdClass,
                Set\Strings::madeOf(Set\Chars::any())->between(1, 2),
            ),
            Set\Integers::between(1, 100),
        );

        foreach ($sequences->values(Random::default) as $value) {
            $dichotomy = $value->shrink();

            $this->assertFalse($dichotomy->a()->isImmutable());
            $this->assertFalse($dichotomy->b()->isImmutable());
        }
    }
}
