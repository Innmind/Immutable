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
}
