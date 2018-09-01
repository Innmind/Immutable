<?php
declare(strict_types = 1);

use Innmind\Immutable\Set;

final class SetBench
{
    private $data;
    private $set;

    public function __construct()
    {
        $this->data = unserialize(file_get_contents(__DIR__.'/fixtures.data'));
        $this->set = Set::of('int', ...$this->data);
    }

    public function benchNamedConstructor()
    {
        Set::of('int', ...$this->data);
    }

    public function benchIntersect()
    {
        $this->set->intersect($this->set);
    }

    public function benchContains()
    {
        $this->set->contains(500);
    }

    public function benchRemove()
    {
        $this->set->remove(500);
    }

    public function benchDiff()
    {
        $this->set->diff($this->set);
    }

    public function benchEquals()
    {
        $this->set->equals($this->set);
    }

    public function benchFilter()
    {
        $this->set->filter(static function(int $i): bool {
            return $i % 2 === 0;
        });
    }

    public function benchForeach()
    {
        $this->set->foreach(static function(int $i): void {
            // pass
        });
    }

    public function benchGroupBy()
    {
        $this->set->groupBy(static function(int $i): int {
            return $i % 2;
        });
    }

    public function benchMap()
    {
        $this->set->map(static function(int $i): int {
            return $i ** 2;
        });
    }

    public function benchPartition()
    {
        $this->set->partition(static function(int $i): bool {
            return $i % 2 === 0;
        });
    }

    public function benchMerge()
    {
        $this->set->merge($this->set);
    }

    public function benchReduce()
    {
        $this->set->reduce(
            0,
            static function(int $sum, int $i): int {
                return $sum + $i;
            }
        );
    }
}
