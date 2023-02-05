<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Maybe;

use Innmind\Immutable\{
    Maybe,
    Either,
};

/**
 * @template V
 * @implements Implementation<V>
 * @psalm-immutable
 * @internal
 */
final class Defer implements Implementation
{
    /** @var callable(): Maybe<V> */
    private $deferred;
    /** @var ?Maybe<V> */
    private ?Maybe $value = null;

    /**
     * @param callable(): Maybe<V> $deferred
     */
    public function __construct(callable $deferred)
    {
        $this->deferred = $deferred;
    }

    public function map(callable $map): self
    {
        return new self(fn() => $this->unwrap()->map($map));
    }

    public function flatMap(callable $map): Maybe
    {
        return Maybe::defer(fn() => $this->unwrap()->flatMap($map));
    }

    public function match(callable $just, callable $nothing)
    {
        return $this->unwrap()->match($just, $nothing);
    }

    public function otherwise(callable $otherwise): Maybe
    {
        return Maybe::defer(fn() => $this->unwrap()->otherwise($otherwise));
    }

    public function filter(callable $predicate): Implementation
    {
        return new self(fn() => $this->unwrap()->filter($predicate));
    }

    public function either(): Either
    {
        return Either::defer(fn() => $this->unwrap()->either());
    }

    /**
     * @return Maybe<V>
     */
    private function unwrap(): Maybe
    {
        /**
         * @psalm-suppress InaccessibleProperty
         * @psalm-suppress ImpureFunctionCall
         */
        return $this->value ??= ($this->deferred)();
    }
}
