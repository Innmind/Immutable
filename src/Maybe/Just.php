<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Maybe;

use Innmind\Immutable\Maybe;

/**
 * @template V
 * @implements Implementation<V>
 * @psalm-immutable
 * @internal
 */
final class Just implements Implementation
{
    /** @var V */
    private $value;

    /**
     * @param V $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function map(callable $map): self
    {
        return new self($map($this->value));
    }

    public function flatMap(callable $map): Maybe
    {
        return $map($this->value);
    }

    public function match(callable $just, callable $nothing)
    {
        return $just($this->value);
    }

    public function otherwise(callable $otherwise): Maybe
    {
        return Maybe::just($this->value);
    }

    public function filter(callable $predicate): Implementation
    {
        if ($predicate($this->value) === true) {
            return $this;
        }

        return new Nothing;
    }
}
