<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

class NumericRange implements PrimitiveInterface, \Iterator
{
    private $start;
    private $end;
    private $step;
    private $key;
    private $current;

    public function __construct(float $start, float $end, float $step = 1)
    {
        $this->start = $start;
        $this->end = $end;
        $this->step = $step;
        $this->key = 0;
        $this->current = $start;
    }

    public function start(): float
    {
        return $this->start;
    }

    public function end(): float
    {
        return $this->end;
    }

    public function step(): float
    {
        return $this->step;
    }

    public function toPrimitive()
    {
        return range($this->start, $this->end, $this->step);
    }

    public function current()
    {
        return $this->current;
    }

    public function key()
    {
        return $this->key;
    }

    public function next()
    {
        ++$this->key;
        $this->current += $this->step;
    }

    public function rewind()
    {
        $this->key = 0;
        $this->current = $this->start;
    }

    public function valid()
    {
        return $this->current < $this->end;
    }
}
