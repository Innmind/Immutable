<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Either;

use Innmind\Immutable\{
    Either,
    Maybe,
};

/**
 * @template L1
 * @template R1
 * @implements Implementation<L1, R1>
 * @psalm-immutable
 * @internal
 */
final class Right implements Implementation
{
    /** @var R1 */
    private $value;

    /**
     * @param R1 $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function map(callable $map): self
    {
        return new self($map($this->value));
    }

    public function flatMap(callable $map): Either
    {
        return $map($this->value);
    }

    /**
     * @template T
     *
     * @param callable(L1): T $map
     *
     * @return self<T, R1>
     */
    public function leftMap(callable $map): self
    {
        /** @var self<T, R1> */
        return $this;
    }

    public function match(callable $right, callable $left)
    {
        return $right($this->value);
    }

    public function otherwise(callable $otherwise): Either
    {
        return Either::right($this->value);
    }

    public function filter(callable $predicate, callable $otherwise): Implementation
    {
        if ($predicate($this->value) === true) {
            return $this;
        }

        return new Left($otherwise());
    }

    public function maybe(): Maybe
    {
        return Maybe::just($this->value);
    }
}
