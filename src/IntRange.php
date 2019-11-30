<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

class IntRange implements \Iterator
{
    private $start;
    private $end;
    private $step;
    private $key;
    private $current;

    public function __construct(int $start, int $end, int $step = 1)
    {
        $this->start = $start;
        $this->end = $end;
        $this->step = $step;
        $this->key = 0;
        $this->current = $start;
    }

    public function start(): int
    {
        return $this->start;
    }

    public function end(): int
    {
        return $this->end;
    }

    public function step(): int
    {
        return $this->step;
    }

    public function current(): int
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
        return $this->current <= $this->end;
    }
}
