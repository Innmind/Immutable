<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Maybe,
    Accumulate,
    SideEffect,
    Identity,
};

/**
 * @template T
 * @implements Implementation<T>
 * @psalm-immutable
 */
final class Defer implements Implementation
{
    /** @var Accumulate<T> */
    private Accumulate $values;
    /** @var \Generator<T> */
    private \Generator $generator;

    /**
     * @param \Generator<T> $generator
     */
    public function __construct(\Generator $generator)
    {
        /**
         * @psalm-suppress ImpureFunctionCall
         */
        $this->values = new Accumulate((static function(\Generator $generator): \Generator {
            $generator->rewind();

            while ($generator->valid()) {
                yield $generator->current();
                $generator->next();
            }
        })($generator));
        $this->generator = $generator;
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function __invoke($element): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(mixed $element) use ($captured): \Generator {
                $values = self::detonate($captured);

                $values->rewind();

                while ($values->valid()) {
                    yield $values->current();
                    $values->next();
                }

                yield $element;
            })($element),
        );
    }

    #[\Override]
    public function size(): int
    {
        return $this->memoize()->size();
    }

    #[\Override]
    public function count(): int
    {
        return $this->size();
    }

    /**
     * @return \Iterator<T>
     */
    #[\Override]
    public function iterator(): \Iterator
    {
        return $this->values;
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function get(int $index): Maybe
    {
        $captured = $this->capture();

        return Maybe::defer(static function() use ($captured, $index) {
            $iteration = 0;
            /** @var \Iterator<T> */
            $values = self::detonate($captured);

            $values->rewind();

            while ($values->valid()) {
                $value = $values->current();

                if ($index === $iteration) {
                    return Maybe::just($value);
                }

                ++$iteration;
                $values->next();
            }

            /** @var Maybe<T> */
            return Maybe::nothing();
        });
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function diff(Implementation $sequence): Implementation
    {
        return $this->filter(static function(mixed $value) use ($sequence): bool {
            /** @var T $value */
            return !$sequence->contains($value);
        });
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function distinct(): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function() use ($captured): \Generator {
                /** @var list<T> */
                $uniques = [];
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    $value = $values->current();

                    if (!\in_array($value, $uniques, true)) {
                        $uniques[] = $value;

                        yield $value;
                    }

                    $values->next();
                }
            })(),
        );
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function drop(int $size): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(int $toDrop) use ($captured): \Generator {
                $dropped = 0;
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    if ($dropped < $toDrop) {
                        ++$dropped;
                        $values->next();

                        continue;
                    }

                    yield $values->current();
                    $values->next();
                }
            })($size),
        );
    }

    /**
     * @param 0|positive-int $size
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function dropEnd(int $size): Implementation
    {
        // this cannot be optimised as the whole generator needs to be loaded
        // in order to know the elements to drop
        return $this->memoize()->dropEnd($size);
    }

    /**
     * @param Implementation<T> $sequence
     */
    #[\Override]
    public function equals(Implementation $sequence): bool
    {
        return $this->memoize()->equals($sequence);
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function filter(callable $predicate): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(callable $predicate) use ($captured): \Generator {
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    $value = $values->current();

                    if ($predicate($value)) {
                        yield $value;
                    }

                    $values->next();
                }
            })($predicate),
        );
    }

    /**
     * @param callable(T): void $function
     */
    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        $this->values->rewind();

        while ($this->values->valid()) {
            /** @psalm-suppress ImpureFunctionCall */
            $function($this->values->current());
            $this->values->next();
        }

        return new SideEffect;
    }

    /**
     * @template D
     * @param callable(T): D $discriminator
     *
     * @return Map<D, Sequence<T>>
     */
    #[\Override]
    public function groupBy(callable $discriminator): Map
    {
        /** @var Map<D, Sequence<T>> */
        return $this->memoize()->groupBy($discriminator);
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function first(): Maybe
    {
        $captured = $this->capture();

        return Maybe::defer(static function() use ($captured) {
            /** @var \Iterator<T> */
            $values = self::detonate($captured);
            $values->rewind();

            while ($values->valid()) {
                return Maybe::just($values->current());
            }

            /** @var Maybe<T> */
            return Maybe::nothing();
        });
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function last(): Maybe
    {
        $captured = $this->capture();

        return Maybe::defer(static function() use ($captured) {
            $loaded = false;
            /** @var \Iterator<T> */
            $values = self::detonate($captured);
            $values->rewind();

            while ($values->valid()) {
                $loaded = true;
                $value = $values->current();
                $values->next();
            }

            if (!$loaded) {
                /** @var Maybe<T> */
                return Maybe::nothing();
            }

            /**
             * @psalm-suppress PossiblyUndefinedVariable
             * @var Maybe<T>
             */
            return Maybe::just($value);
        });
    }

    /**
     * @param T $element
     */
    #[\Override]
    public function contains($element): bool
    {
        $this->values->rewind();

        while ($this->values->valid()) {
            if ($this->values->current() === $element) {
                return true;
            }

            $this->values->next();
        }

        return false;
    }

    /**
     * @param T $element
     *
     * @return Maybe<0|positive-int>
     */
    #[\Override]
    public function indexOf($element): Maybe
    {
        $captured = $this->capture();

        return Maybe::defer(static function() use ($captured, $element) {
            $index = 0;
            /** @var \Iterator<T> */
            $values = self::detonate($captured);
            $values->rewind();

            while ($values->valid()) {
                if ($values->current() === $element) {
                    /** @var Maybe<0|positive-int> */
                    return Maybe::just($index);
                }

                ++$index;
                $values->next();
            }

            /** @var Maybe<0|positive-int> */
            return Maybe::nothing();
        });
    }

    /**
     * Return the list of indices
     *
     * @return Implementation<0|positive-int>
     */
    #[\Override]
    public function indices(): Implementation
    {
        $captured = $this->capture();

        /**
         * @psalm-suppress ImpureFunctionCall
         * @var Implementation<0|positive-int>
         */
        return new self(
            (static function() use ($captured): \Generator {
                $index = 0;
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    yield $index++;
                    $values->next();
                }
            })(),
        );
    }

    /**
     * @template S
     *
     * @param callable(T): S $function
     *
     * @return Implementation<S>
     */
    #[\Override]
    public function map(callable $function): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(callable $map) use ($captured): \Generator {
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    yield $map($values->current());
                    $values->next();
                }
            })($function),
        );
    }

    /**
     * @template S
     * @template C of Sequence<S>|Set<S>
     *
     * @param callable(T): C $map
     * @param callable(C): Implementation<S> $exfiltrate
     *
     * @return self<S>
     */
    #[\Override]
    public function flatMap(callable $map, callable $exfiltrate): self
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(callable $map, callable $exfiltrate) use ($captured): \Generator {
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    /**
                     * @var callable(T): C $map
                     * @var callable(C): Implementation<S> $exfiltrate
                     * @psalm-suppress PossiblyNullArgument
                     */
                    $inner = $exfiltrate($map($values->current()))->iterator();
                    $inner->rewind();

                    while ($inner->valid()) {
                        yield $inner->current();
                        $inner->next();
                    }

                    $values->next();
                }
            })($map, $exfiltrate),
        );
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function pad(int $size, $element): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(int $toPad, mixed $element) use ($captured): \Generator {
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    yield $values->current();
                    --$toPad;
                    $values->next();
                }

                while ($toPad > 0) {
                    yield $element;
                    --$toPad;
                }
            })($size, $element),
        );
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Map<bool, Sequence<T>>
     */
    #[\Override]
    public function partition(callable $predicate): Map
    {
        /** @var Map<bool, Sequence<T>> */
        return $this->memoize()->partition($predicate);
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function slice(int $from, int $until): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(int $from, int $until) use ($captured): \Generator {
                $index = 0;
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    if ($index >= $from && $index < $until) {
                        yield $values->current();
                    }

                    ++$index;
                    $values->next();
                }
            })($from, $until),
        );
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function take(int $size): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(int $size) use ($captured): \Generator {
                $taken = 0;
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    // TODO inline this call when Accumulate implementation is fixed
                    $value = $values->current();

                    if ($taken >= $size) {
                        return;
                    }

                    yield $value;
                    ++$taken;
                    $values->next();
                }
            })($size),
        );
    }

    /**
     * @param 0|positive-int $size
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function takeEnd(int $size): Implementation
    {
        // this cannot be optimised as the whole generator needs to be loaded
        // in order to know the elements to drop
        return $this->memoize()->takeEnd($size);
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function append(Implementation $sequence): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(Implementation $sequence) use ($captured): \Generator {
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    yield $values->current();
                    $values->next();
                }

                $iterator = $sequence->iterator();
                $iterator->rewind();

                while ($iterator->valid()) {
                    yield $iterator->current();
                    $iterator->next();
                }
            })($sequence),
        );
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function prepend(Implementation $sequence): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(Implementation $sequence) use ($captured): \Generator {
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $iterator = $sequence->iterator();
                $iterator->rewind();

                while ($iterator->valid()) {
                    yield $iterator->current();
                    $iterator->next();
                }

                $values->rewind();

                while ($values->valid()) {
                    yield $values->current();
                    $values->next();
                }
            })($sequence),
        );
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function intersect(Implementation $sequence): Implementation
    {
        return $this->filter(static function(mixed $value) use ($sequence): bool {
            /** @var T $value */
            return $sequence->contains($value);
        });
    }

    /**
     * @param callable(T, T): int $function
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function sort(callable $function): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(callable $function) use ($captured): \Generator {
                /** @var callable(T, T): int $sorter */
                $sorter = $function;
                $loaded = [];
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    $loaded[] = $values->current();
                    $values->next();
                }

                \usort($loaded, $sorter);

                foreach ($loaded as $value) {
                    yield $value;
                }
            })($function),
        );
    }

    /**
     * @template I
     * @template R
     *
     * @param I $carry
     * @param callable(I|R, T): R $reducer
     *
     * @return I|R
     */
    #[\Override]
    public function reduce($carry, callable $reducer)
    {
        $this->values->rewind();

        while ($this->values->valid()) {
            /** @psalm-suppress ImpureFunctionCall */
            $carry = $reducer($carry, $this->values->current());
            $this->values->next();
        }

        return $carry;
    }

    /**
     * @template I
     *
     * @param I $carry
     * @param callable(I, T, Sink\Continuation<I>): Sink\Continuation<I> $reducer
     *
     * @return I
     */
    #[\Override]
    public function sink($carry, callable $reducer): mixed
    {
        $continuation = Sink\Continuation::of($carry);
        $this->values->rewind();

        while ($this->values->valid()) {
            /** @psalm-suppress ImpureFunctionCall */
            $continuation = $reducer($carry, $this->values->current(), $continuation);
            $carry = $continuation->unwrap();

            if (!$continuation->shouldContinue()) {
                return $continuation->unwrap();
            }

            $this->values->next();
        }

        return $continuation->unwrap();
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function clear(): Implementation
    {
        return new Primitive;
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function reverse(): Implementation
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function() use ($captured): \Generator {
                $reversed = [];
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    \array_unshift($reversed, $values->current());
                    $values->next();
                }

                foreach ($reversed as $value) {
                    yield $value;
                }
            })(),
        );
    }

    #[\Override]
    public function empty(): bool
    {
        /** @psalm-suppress ImpureMethodCall */
        $this->values->rewind();

        /** @psalm-suppress ImpureMethodCall */
        return !$this->values->valid();
    }

    #[\Override]
    public function toIdentity(): Identity
    {
        /** @var Identity<Implementation<T>> */
        return Identity::defer(fn() => $this);
    }

    /**
     * @return Sequence<T>
     */
    #[\Override]
    public function toSequence(): Sequence
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return Sequence::defer(
            (static function() use ($captured): \Generator {
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    yield $values->current();
                    $values->next();
                }
            })(),
        );
    }

    /**
     * @return Set<T>
     */
    #[\Override]
    public function toSet(): Set
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return Set::defer(
            (static function() use ($captured): \Generator {
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    yield $values->current();
                    $values->next();
                }
            })(),
        );
    }

    #[\Override]
    public function find(callable $predicate): Maybe
    {
        $captured = $this->capture();

        return Maybe::defer(static function() use ($captured, $predicate) {
            /** @var \Iterator<T> */
            $values = self::detonate($captured);
            $values->rewind();

            while ($values->valid()) {
                $value = $values->current();

                /**
                 * @psalm-suppress ImpureFunctionCall
                 * @psalm-suppress PossiblyNullArgument
                 */
                if ($predicate($value) === true) {
                    return Maybe::just($value);
                }

                $values->next();
            }

            /** @var Maybe<T> */
            return Maybe::nothing();
        });
    }

    #[\Override]
    public function match(callable $wrap, callable $match, callable $empty)
    {
        /** @psalm-suppress MixedArgument */
        return $this
            ->first()
            ->match(
                fn($first) => $match($first, $wrap($this->drop(1))),
                $empty,
            );
    }

    /**
     * @template S
     *
     * @param Implementation<S> $sequence
     *
     * @return Implementation<array{T, S}>
     */
    #[\Override]
    public function zip(Implementation $sequence): Implementation
    {
        $captured = $this->capture();

        /**
         * @psalm-suppress ImpureFunctionCall
         * @var Implementation<array{T, S}>
         */
        return new self(
            (static function(\Iterator $other) use ($captured) {
                /** @var \Iterator<T> $self */
                $self = self::detonate($captured);
                $self->rewind();
                // todo $other->rewind()

                while ($self->valid()) {
                    if (!$other->valid()) {
                        return;
                    }

                    yield [$self->current(), $other->current()];
                    $self->next();
                    $other->next();
                }
            })($sequence->iterator()),
        );
    }

    /**
     * @template R
     * @param R $carry
     * @param callable(R, T): R $assert
     *
     * @return self<T>
     */
    #[\Override]
    public function safeguard($carry, callable $assert): self
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(mixed $carry, callable $assert) use ($captured): \Generator {
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    $value = $values->current();
                    /** @var R */
                    $carry = $assert($carry, $value);

                    yield $value;

                    $values->next();
                }
            })($carry, $assert),
        );
    }

    /**
     * @template A
     *
     * @param callable(T|A, T): Sequence<A> $map
     * @param callable(Sequence<A>): Implementation<A> $exfiltrate
     *
     * @return self<T|A>
     */
    #[\Override]
    public function aggregate(callable $map, callable $exfiltrate): self
    {
        $aggregate = new Aggregate($this->iterator());

        /** @psalm-suppress MixedArgument */
        return new self($aggregate(static fn($a, $b) => $exfiltrate($map($a, $b))->iterator()));
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function memoize(): Implementation
    {
        $values = [];
        $this->values->rewind();

        while ($this->values->valid()) {
            $values[] = $this->values->current();
            $this->values->next();
        }

        return new Primitive($values);
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return self<T>
     */
    #[\Override]
    public function dropWhile(callable $condition): self
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self((static function(callable $condition) use ($captured) {
            /** @var \Iterator<T> */
            $values = self::detonate($captured);
            // todo $values->rewind();

            /** @psalm-suppress ImpureMethodCall */
            while ($values->valid()) {
                /**
                 * @psalm-suppress ImpureMethodCall
                 * @psalm-suppress ImpureFunctionCall
                 */
                if (!$condition($values->current())) {
                    /** @psalm-suppress ImpureMethodCall */
                    yield $values->current();
                    /** @psalm-suppress ImpureMethodCall */
                    $values->next();

                    break;
                }

                /** @psalm-suppress ImpureMethodCall */
                $values->next();
            }

            /** @psalm-suppress ImpureMethodCall */
            while ($values->valid()) {
                /** @psalm-suppress ImpureMethodCall */
                yield $values->current();
                /** @psalm-suppress ImpureMethodCall */
                $values->next();
            }
        })($condition));
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return self<T>
     */
    #[\Override]
    public function takeWhile(callable $condition): self
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(callable $condition) use ($captured): \Generator {
                /** @var \Iterator<T> */
                $values = self::detonate($captured);
                $values->rewind();

                while ($values->valid()) {
                    $value = $values->current();

                    if (!$condition($value)) {
                        return;
                    }

                    yield $value;
                    $values->next();
                }
            })($condition),
        );
    }

    /**
     * @return array{\WeakReference<self<T>>, \Iterator<T>}
     */
    private function capture(): array
    {
        /** @psalm-suppress ImpureMethodCall */
        return [
            \WeakReference::create($this),
            match ($this->values->started()) {
                true => $this->values,
                false => $this->generator,
            },
        ];
    }

    /**
     * @template V
     *
     * @param array{\WeakReference<self<V>>, \Iterator<V>} $captured
     *
     * @return \Iterator<V>
     */
    private static function detonate(array $captured): \Iterator
    {
        [$ref, $generator] = $captured;
        $self = $ref->get();

        if (\is_null($self)) {
            return $generator;
        }

        return $self->values;
    }
}
