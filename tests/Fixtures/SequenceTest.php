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
            $this->assertInstanceOf(Structure::class, $sequence);
            $this->assertSame('string', (string) $sequence->type());
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
            $sizes[] = $sequence->size();
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
        $sequences1 = new Sequence('string', new Set\Chars);
        $sequences2 = $sequences1->filter(static function($sequence): bool {
            return $sequence->size() % 2 === 0;
        });

        $this->assertNotSame($sequences1, $sequences2);
        $this->assertInstanceOf(Sequence::class, $sequences2);

        $values1 = \iterator_to_array($sequences1->values());
        $values2 = \iterator_to_array($sequences2->values());
        $values1 = \array_map(function($sequence) {
            return $sequence->size() % 2;
        }, $values1);
        $values2 = \array_map(function($sequence) {
            return $sequence->size() % 2;
        }, $values2);
        $values1 = \array_unique($values1);
        $values2 = \array_unique($values2);
        \sort($values1);

        $this->assertSame([0, 1], \array_values($values1));
        $this->assertSame([0], \array_values($values2));
    }
}
