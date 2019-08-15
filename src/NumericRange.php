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

    /**
     * @return array<float>
     */
    public function toPrimitive(): array
    {
        return \range($this->start, $this->end, $this->step);
    }

    public function current(): float
    {
        return $this->current;
    }

    public function key(): int
    {
        return $this->key;
    }

    public function next(): void
    {
        ++$this->key;
        $this->current += $this->step;
    }

    public function rewind(): void
    {
        $this->key = 0;
        $this->current = $this->start;
    }

    public function valid(): bool
    {
        return $this->current < $this->end;
    }
}
