<?php
declare(strict_types = 1);

use Innmind\Immutable\Sequence;

final class SequenceBench
{
    private $data;
    private $sequence;

    public function __construct()
    {
        $this->data = unserialize(file_get_contents(__DIR__.'/fixtures.data'));
        $this->sequence = new Sequence(...$this->data);
    }

    public function benchNamedConstructor()
    {
        Sequence::of(...$this->data);
    }

    public function benchMap()
    {
        $this->sequence->map(static function(int $i): int {
            return $i ** 2;
        });
    }

    public function benchGet()
    {
        $this->sequence->get(500);
    }

    public function benchHas()
    {
        $this->sequence->has(500);
    }

    public function benchIndexOf()
    {
        $this->sequence->indexOf(500);
    }

    public function benchDistinct()
    {
        $this->sequence->indexOf(500);
    }

    public function benchDiff()
    {
        $this->sequence->diff($this->sequence);
    }

    public function benchAppend()
    {
        $this->sequence->append($this->sequence);
    }

    public function benchContains()
    {
        $this->sequence->contains(500);
    }

    public function benchDrop()
    {
        $this->sequence->drop(500);
    }

    public function benchDropEnd()
    {
        $this->sequence->dropEnd(500);
    }

    public function benchEquals()
    {
        $this->sequence->equals($this->sequence);
    }

    public function benchFilter()
    {
        $this->sequence->filter(static function(int $i): bool {
            return $i % 2 === 0;
        });
    }

    public function benchForeach()
    {
        $this->sequence->foreach(static function(int $i): void {
            // pass
        });
    }

    public function benchGroupBy()
    {
        $this->sequence->groupBy(static function(int $i): int {
            return $i % 2;
        });
    }

    public function benchFirst()
    {
        $this->sequence->first();
    }

    public function benchLast()
    {
        $this->sequence->last();
    }

    public function benchIndices()
    {
        $this->sequence->indices();
    }

    public function benchTake()
    {
        $this->sequence->take(500);
    }

    public function benchTakeEnd()
    {
        $this->sequence->takeEnd(500);
    }

    public function benchIntersect()
    {
        $this->sequence->intersect($this->sequence);
    }

    public function benchSort()
    {
        $this->sequence->sort(static function(): int {
            return -1;
        });
    }

    public function benchReduce()
    {
        $this->sequence->reduce(
            0,
            static function(int $sum, int $i): int {
                return $sum + $i;
            }
        );
    }

    public function benchReverse()
    {
        $this->sequence->reverse();
    }
}
