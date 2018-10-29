<?php
declare(strict_types = 1);

use Innmind\Immutable\Map;

final class MapDoubleIndexBench
{
    private $data;
    private $map;

    public function __construct()
    {
        $this->data = unserialize(file_get_contents(__DIR__.'/fixtures.data'));
        $this->map = Map::of(
            'array',
            'int',
            array_map(function(int $i) {
                return [$i];
            }, array_keys($this->data)),
            array_values($this->data)
        );
    }

    public function benchNamedConstructor()
    {
        Map::of(
            'array',
            'int',
            array_map(function(int $i) {
                return [$i];
            }, array_keys($this->data)),
            array_values($this->data)
        );
    }

    public function benchGet()
    {
        $this->map->get([500]);
    }

    public function benchContains()
    {
        $this->map->contains([500]);
    }

    public function benchEquals()
    {
        $this->map->equals($this->map);
    }

    public function benchFilter()
    {
        $this->map->filter(static function(array $k, int $v): bool {
            return $v % 2 === 0;
        });
    }

    public function benchForeach()
    {
        $this->map->foreach(static function(array $k, int $v): void {
            // pass
        });
    }

    public function benchGroupBy()
    {
        $this->map->groupBy(static function(array $k, int $v): int {
            return $v % 2;
        });
    }

    public function benchKeys()
    {
        $this->map->keys();
    }

    public function benchValues()
    {
        $this->map->values();
    }

    public function benchMap()
    {
        $this->map->map(static function(array $k, int $i): int {
            return $i ** 2;
        });
    }

    public function benchRemove()
    {
        $this->map->remove([500]);
    }

    public function benchMerge()
    {
        $this->map->merge($this->map);
    }

    public function benchPartition()
    {
        $this->map->partition(static function(array $k, int $v): bool {
            return $v % 2 === 0;
        });
    }

    public function benchReduce()
    {
        $this->map->reduce(
            0,
            static function(int $sump, array $k, int $v): int {
                return $sum + $v;
            }
        );
    }
}
