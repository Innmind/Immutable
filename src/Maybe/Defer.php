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

    #[\Override]
    public function map(callable $map): self
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->map($map));
    }

    #[\Override]
    public function flatMap(callable $map): Maybe
    {
        $captured = $this->capture();

        return Maybe::defer(static fn() => self::detonate($captured)->flatMap($map));
    }

    #[\Override]
    public function match(callable $just, callable $nothing)
    {
        return $this->unwrap()->match($just, $nothing);
    }

    #[\Override]
    public function otherwise(callable $otherwise): Maybe
    {
        $captured = $this->capture();

        return Maybe::defer(static fn() => self::detonate($captured)->otherwise($otherwise));
    }

    #[\Override]
    public function filter(callable $predicate): Implementation
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->filter($predicate));
    }

    #[\Override]
    public function either(): Either
    {
        $captured = $this->capture();

        return Either::defer(static fn() => self::detonate($captured)->either());
    }

    #[\Override]
    public function attempt(callable $error): Attempt
    {
        $captured = $this->capture();

        return Attempt::defer(static fn() => self::detonate($captured)->attempt($error));
    }

    /**
     * @return Maybe<V>
     */
    #[\Override]
    public function memoize(): Maybe
    {
        return $this->unwrap();
    }

    #[\Override]
    public function toSequence(): Sequence
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return Sequence::defer((static function() use ($captured) {
            /** @var V $value */
            foreach (self::detonate($captured)->toSequence()->toList() as $value) {
                yield $value;
            }
        })());
    }

    #[\Override]
    public function eitherWay(callable $just, callable $nothing): Maybe
    {
        $captured = $this->capture();

        return Maybe::defer(static fn() => self::detonate($captured)->eitherWay($just, $nothing));
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
        return $this->value ??= ($this->deferred)()->memoize();
    }

    /**
     * @return array{\WeakReference<self<V>>, callable(): Maybe<V>}
     */
    private function capture(): array
    {
        /** @psalm-suppress ImpureMethodCall */
        return [
            \WeakReference::create($this),
            $this->deferred,
        ];
    }

    /**
     * @template T
     *
     * @param array{\WeakReference<self<T>>, callable(): Maybe<T>} $captured
     *
     * @return Maybe<T>
     */
    private static function detonate(array $captured): Maybe
    {
        [$ref, $deferred] = $captured;
        $self = $ref->get();

        if (\is_null($self)) {
            return $deferred();
        }

        return $self->unwrap();
    }
}
