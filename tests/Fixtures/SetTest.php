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
            $this->assertInstanceOf(Structure::class, $set);
            $this->assertSame('string', (string) $set->type());
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
            $sizes[] = $set->size();
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
        $sets1 = new Set('string', new DataSet\Chars);
        $sets2 = $sets1->filter(static function($set): bool {
            return $set->size() % 2 === 0;
        });

        $this->assertNotSame($sets1, $sets2);
        $this->assertInstanceOf(Set::class, $sets2);

        $values1 = \iterator_to_array($sets1->values());
        $values2 = \iterator_to_array($sets2->values());
        $values1 = \array_map(function($set) {
            return $set->size() % 2;
        }, $values1);
        $values2 = \array_map(function($set) {
            return $set->size() % 2;
        }, $values2);
        $values1 = \array_unique($values1);
        $values2 = \array_unique($values2);
        \sort($values1);

        $this->assertSame([0, 1], \array_values($values1));
        $this->assertSame([0], \array_values($values2));
    }
}
