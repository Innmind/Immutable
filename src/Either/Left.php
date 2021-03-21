<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Either;

use Innmind\Immutable\Either;

/**
 * @template L1
 * @template R1
 * @implements Implementation<L1, R1>
 * @psalm-immutable
 * @internal
 */
final class Left implements Implementation
{
    /** @var L1 */
    private $value;

    /**
     * @param L1 $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @template T
     *
     * @param callable(R1): T $map
     *
     * @return self<L1, T>
     */
    public function map(callable $map): self
    {
        /** @var self<L1, T> */
        return $this;
    }

    public function flatMap(callable $map): Either
    {
        return Either::left($this->value);
    }

    public function match(callable $left, callable $right)
    {
        return $left($this->value);
    }

    public function otherwise(callable $otherwise): Either
    {
        return $otherwise();
    }

    public function filter(callable $predicate, callable $otherwise): self
    {
        return $this;
    }
}
