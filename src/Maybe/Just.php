<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Maybe;

use Innmind\Immutable\{
    Maybe,
    Either,
    Sequence,
};

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

    #[\Override]
    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->value));
    }

    #[\Override]
    public function flatMap(callable $map): Maybe
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $map($this->value);
    }

    #[\Override]
    public function match(callable $just, callable $nothing)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $just($this->value);
    }

    #[\Override]
    public function otherwise(callable $otherwise): Maybe
    {
        return Maybe::just($this->value);
    }

    #[\Override]
    public function filter(callable $predicate): Implementation
    {
        /** @psalm-suppress ImpureFunctionCall */
        if ($predicate($this->value) === true) {
            return $this;
        }

        return new Nothing;
    }

    #[\Override]
    public function either(): Either
    {
        return Either::right($this->value);
    }

    /**
     * @return Maybe<V>
     */
    #[\Override]
    public function memoize(): Maybe
    {
        return Maybe::just($this->value);
    }

    #[\Override]
    public function toSequence(): Sequence
    {
        return Sequence::of($this->value);
    }

    #[\Override]
    public function eitherWay(callable $just, callable $nothing): Maybe
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $just($this->value);
    }
}
