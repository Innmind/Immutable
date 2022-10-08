<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Maybe;

use Innmind\Immutable\{
    Maybe,
    Either,
};

/**
 * @psalm-immutable
 * @internal
 */
final class Nothing implements Implementation
{
    public function map(callable $map): self
    {
        return $this;
    }

    public function flatMap(callable $map): Maybe
    {
        return Maybe::nothing();
    }

    public function match(callable $just, callable $nothing)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $nothing();
    }

    public function otherwise(callable $otherwise): Maybe
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $otherwise();
    }

    public function filter(callable $predicate): self
    {
        return $this;
    }

    public function either(): Either
    {
        return Either::left(null);
    }
}
