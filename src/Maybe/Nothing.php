<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Maybe;

use Innmind\Immutable\{
    Maybe,
    Either,
    Sequence,
    Attempt,
};

/**
 * @psalm-immutable
 * @implements Implementation<empty>
 * @internal
 */
final class Nothing implements Implementation
{
    #[\Override]
    public function map(callable $map): self
    {
        return $this;
    }

    #[\Override]
    public function flatMap(callable $map): Maybe
    {
        return Maybe::nothing();
    }

    #[\Override]
    public function match(callable $just, callable $nothing)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $nothing();
    }

    #[\Override]
    public function otherwise(callable $otherwise): Maybe
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $otherwise();
    }

    #[\Override]
    public function filter(callable $predicate): self
    {
        return $this;
    }

    #[\Override]
    public function either(): Either
    {
        return Either::left(null);
    }

    #[\Override]
    public function attempt(callable $error): Attempt
    {
        /** @psalm-suppress ImpureFunctionCall */
        return Attempt::error($error());
    }

    /**
     * @return Maybe<empty>
     */
    #[\Override]
    public function memoize(): Maybe
    {
        /** @var Maybe<empty> */
        return Maybe::nothing();
    }

    #[\Override]
    public function toSequence(): Sequence
    {
        return Sequence::of();
    }

    #[\Override]
    public function eitherWay(callable $just, callable $nothing): Maybe
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $nothing();
    }
}
