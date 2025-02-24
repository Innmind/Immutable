<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Maybe,
    SideEffect,
    RegisterCleanup,
    Identity,
};

/**
 * @template T
 * @implements Implementation<T>
 * @psalm-immutable
 */
final class Lazy implements Implementation
{
    /** @var \Closure(RegisterCleanup): \Generator<int<0, max>, T> */
    private \Closure $values;

    /**
     * @param callable(RegisterCleanup): \Generator<T> $generator
     */
    public function __construct(callable $generator)
    {
        /** @var \Closure(RegisterCleanup): \Generator<int<0, max>, T> */
        $this->values = \Closure::fromCallable($generator);
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function __invoke($element): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $element): \Generator {
                $iterator = $values($registerCleanup);
                $iterator->rewind();

                while ($iterator->valid()) {
                    yield $iterator->current();
                    $iterator->next();
                }

                yield $element;
            },
        );
    }

    #[\Override]
    public function size(): int
    {
        $size = 0;
        $iterator = $this->iterator();

        /** @psalm-suppress ImpureMethodCall */
        while ($iterator->valid()) {
            ++$size;
            /** @psalm-suppress ImpureMethodCall */
            $iterator->next();
        }

        return $size;
    }

    #[\Override]
    public function count(): int
    {
        return $this->size();
    }

    /**
     * @return Iterator<T>
     */
    #[\Override]
    public function iterator(): Iterator
    {
        // when accessing the iterator from the outside we cannot know when it
        // will be stopped being iterated over so we can't have a way to notify
        // the generator to cleanup its ressources, so we pass an empty function
        // that does nothing
        return Iterator::lazy($this->values, RegisterCleanup::noop());
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function get(int $index): Maybe
    {
        $values = $this->values;

        return Maybe::defer(static function() use ($values, $index) {
            $iteration = 0;
            $register = RegisterCleanup::noop();
            $iterator = $values($register);

            while ($iterator->valid()) {
                $value = $iterator->current();

                if ($index === $iteration) {
                    $register->cleanup();

                    return Maybe::just($value);
                }

                ++$iteration;
                $iterator->next();
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
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values): \Generator {
                /** @var list<T> */
                $uniques = [];
                $iterator = $values($registerCleanup);
                $iterator->rewind();

                while ($iterator->valid()) {
                    $value = $iterator->current();

                    if (!\in_array($value, $uniques, true)) {
                        $uniques[] = $value;

                        yield $value;
                    }

                    $iterator->next();
                }
            },
        );
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function drop(int $size): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $size): \Generator {
                $dropped = 0;
                $iterator = $values($registerCleanup);
                $iterator->rewind();

                while ($iterator->valid()) {
                    $value = $iterator->current();
                    $iterator->next();

                    if ($dropped < $size) {
                        ++$dropped;

                        continue;
                    }

                    yield $value;
                }
            },
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
        return $this->load()->dropEnd($size);
    }

    /**
     * @param Implementation<T> $sequence
     */
    #[\Override]
    public function equals(Implementation $sequence): bool
    {
        return $this->load()->equals($sequence);
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function filter(callable $predicate): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $predicate): \Generator {
                $iterator = $values($registerCleanup);
                $iterator->rewind();

                while ($iterator->valid()) {
                    /** @var T */
                    $value = $iterator->current();

                    if ($predicate($value)) {
                        yield $value;
                    }

                    $iterator->next();
                }
            },
        );
    }

    /**
     * @param callable(T): void $function
     */
    #[\Override]
    public function foreach(callable $function): SideEffect
    {
        $iterator = $this->iterator();
        /** @psalm-suppress ImpureMethodCall */
        $iterator->rewind();

        /** @psalm-suppress ImpureMethodCall */
        while ($iterator->valid()) {
            /**
             * @psalm-suppress ImpureFunctionCall
             * @psalm-suppress ImpureMethodCall
             * @psalm-suppress PossiblyNullArgument
             */
            $function($iterator->current());
            /** @psalm-suppress ImpureMethodCall */
            $iterator->next();
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
        return $this->load()->groupBy($discriminator);
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function first(): Maybe
    {
        return $this->get(0);
    }

    /**
     * @return Maybe<T>
     */
    #[\Override]
    public function last(): Maybe
    {
        $values = $this->values;

        return Maybe::defer(static function() use ($values) {
            $loaded = false;
            // No-op as we iterate over the whole iterator so the default
            // cleanup defined in the callable will be called.
            $register = RegisterCleanup::noop();
            $iterator = $values($register);
            $iterator->rewind();

            while ($iterator->valid()) {
                $value = $iterator->current();
                $loaded = true;
                $iterator->next();
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
        $register = RegisterCleanup::noop();
        /** @psalm-suppress ImpureFunctionCall */
        $iterator = ($this->values)($register);
        /** @psalm-suppress ImpureMethodCall */
        $iterator->rewind();

        /** @psalm-suppress ImpureMethodCall */
        while ($iterator->valid()) {
            /** @psalm-suppress ImpureMethodCall */
            if ($iterator->current() === $element) {
                /** @psalm-suppress ImpureMethodCall */
                $register->cleanup();

                return true;
            }

            /** @psalm-suppress ImpureMethodCall */
            $iterator->next();
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
        $values = $this->values;

        return Maybe::defer(static function() use ($values, $element) {
            $index = 0;
            $register = RegisterCleanup::noop();
            $iterator = $values($register);
            $iterator->rewind();

            while ($iterator->valid()) {
                if ($iterator->current() === $element) {
                    $register->cleanup();

                    /** @var Maybe<0|positive-int> */
                    return Maybe::just($index);
                }

                ++$index;
                $iterator->next();
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
        $values = $this->values;

        /** @var Implementation<0|positive-int> */
        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values): \Generator {
                $index = 0;
                $iterator = $values($registerCleanup);
                $iterator->rewind();

                while ($iterator->valid()) {
                    yield $index++;
                    $iterator->next();
                }
            },
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
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $function): \Generator {
                $iterator = $values($registerCleanup);
                $iterator->rewind();

                while ($iterator->valid()) {
                    /** @psalm-suppress PossiblyNullArgument */
                    yield $function($iterator->current());
                    $iterator->next();
                }
            },
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
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $map, $exfiltrate): \Generator {
                $outer = $values($registerCleanup);
                $outer->rewind();

                while ($outer->valid()) {
                    /** @psalm-suppress PossiblyNullArgument */
                    $inner = self::open(
                        $exfiltrate($map($outer->current())),
                        $registerCleanup->push(),
                    );
                    $inner->rewind();

                    while ($inner->valid()) {
                        yield $inner->current();
                        $inner->next();
                    }

                    $outer->next();
                }
            },
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
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $size, $element): \Generator {
                $iterator = $values($registerCleanup);
                $iterator->rewind();

                while ($iterator->valid()) {
                    yield $iterator->current();
                    --$size;
                    $iterator->next();
                }

                while ($size > 0) {
                    yield $element;
                    --$size;
                }
            },
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
        return $this->load()->partition($predicate);
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function slice(int $from, int $until): Implementation
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        return $this
            ->drop($from)
            ->take($until - $from);
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function take(int $size): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $register) use ($values, $size): \Generator {
                if ($size === 0) {
                    return;
                }

                $taken = 0;
                // We intercept the registering of the cleanup function here
                // because this generator can be stopped when we reach the number
                // of elements to take so we have to cleanup here. In this case
                // the parent sequence may not need to cleanup as it could
                // iterate over the whole generator but this inner one still
                // needs to free resources correctly
                $middleware = $register->push();
                $iterator = $values($middleware);
                $iterator->rewind();

                while ($iterator->valid()) {
                    yield $iterator->current();
                    ++$taken;

                    if ($taken >= $size) {
                        $middleware->cleanup();
                        $register->pop();

                        return;
                    }

                    $iterator->next();
                }
            },
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
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $register) use ($values, $size): \Generator {
                $buffer = [];
                $count = 0;
                $iterator = $values($register);
                $iterator->rewind();

                while ($iterator->valid()) {
                    $buffer[] = $iterator->current();
                    ++$count;

                    if ($count > $size) {
                        \array_shift($buffer);
                    }

                    $iterator->next();
                }

                foreach ($buffer as $value) {
                    yield $value;
                }
            },
        );
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    #[\Override]
    public function append(Implementation $sequence): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $sequence): \Generator {
                $self = $values($registerCleanup);
                $self->rewind();

                while ($self->valid()) {
                    yield $self->current();
                    $self->next();
                }

                /** @var \Iterator<int, T> */
                $iterator = self::open($sequence, $registerCleanup);
                $iterator->rewind();

                while ($iterator->valid()) {
                    yield $iterator->current();
                    $iterator->next();
                }
            },
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
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $sequence): \Generator {
                /** @var \Iterator<int, T> */
                $iterator = self::open($sequence, $registerCleanup);
                $iterator->rewind();

                while ($iterator->valid()) {
                    yield $iterator->current();
                    $iterator->next();
                }

                $self = $values($registerCleanup);
                $self->rewind();

                while ($self->valid()) {
                    yield $self->current();
                    $self->next();
                }
            },
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
        $values = $this->values;

        return new self(
            static function() use ($values, $function): \Generator {
                $loaded = [];

                // bypass the registering of cleanup function as we iterate over
                // the whole generator
                $iterator = $values(RegisterCleanup::noop());
                $iterator->rewind();

                while ($iterator->valid()) {
                    $loaded[] = $iterator->current();
                    $iterator->next();
                }

                \usort($loaded, $function);

                foreach ($loaded as $value) {
                    yield $value;
                }
            },
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
        $iterator = $this->iterator();
        /** @psalm-suppress ImpureMethodCall */
        $iterator->rewind();

        /** @psalm-suppress ImpureMethodCall */
        while ($iterator->valid()) {
            /**
             * @psalm-suppress ImpureFunctionCall
             * @psalm-suppress ImpureMethodCall
             * @psalm-suppress PossiblyNullArgument
             */
            $carry = $reducer($carry, $iterator->current());
            /** @psalm-suppress ImpureMethodCall */
            $iterator->next();
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
        $register = RegisterCleanup::noop();
        /** @psalm-suppress ImpureFunctionCall */
        $iterator = ($this->values)($register);
        /** @psalm-suppress ImpureMethodCall */
        $iterator->rewind();

        /** @psalm-suppress ImpureMethodCall */
        while ($iterator->valid()) {
            /**
             * @psalm-suppress ImpureFunctionCall
             * @psalm-suppress PossiblyNullArgument
             */
            $continuation = $reducer(
                $carry,
                $iterator->current(),
                $continuation,
            );
            $carry = $continuation->unwrap();

            if (!$continuation->shouldContinue()) {
                /** @psalm-suppress ImpureMethodCall */
                $register->cleanup();

                break;
            }

            $iterator->next();
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
        $values = $this->values;

        return new self(
            static function() use ($values): \Generator {
                $reversed = [];

                // bypass the registering of cleanup function as we iterate over
                // the whole generator
                $iterator = $values(RegisterCleanup::noop());
                $iterator->rewind();

                while ($iterator->valid()) {
                    \array_unshift($reversed, $iterator->current());
                    $iterator->next();
                }

                foreach ($reversed as $value) {
                    yield $value;
                }
            },
        );
    }

    #[\Override]
    public function empty(): bool
    {
        /** @psalm-suppress ImpureMethodCall */
        return !$this->iterator()->valid();
    }

    #[\Override]
    public function toIdentity(): Identity
    {
        /** @var Identity<Implementation<T>> */
        return Identity::lazy(fn() => $this);
    }

    /**
     * @return Sequence<T>
     */
    #[\Override]
    public function toSequence(): Sequence
    {
        return Sequence::lazy($this->values);
    }

    /**
     * @return Set<T>
     */
    #[\Override]
    public function toSet(): Set
    {

        return Set::lazy($this->values);
    }

    #[\Override]
    public function find(callable $predicate): Maybe
    {
        $values = $this->values;

        return Maybe::defer(static function() use ($values, $predicate) {
            $register = RegisterCleanup::noop();
            $iterator = $values($register);
            $iterator->rewind();

            while ($iterator->valid()) {
                /** @var T */
                $value = $iterator->current();

                /** @psalm-suppress ImpureFunctionCall */
                if ($predicate($value) === true) {
                    $register->cleanup();

                    return Maybe::just($value);
                }

                $iterator->next();
            }

            /** @var Maybe<T> */
            return Maybe::nothing();
        });
    }

    #[\Override]
    public function match(callable $wrap, callable $match, callable $empty)
    {
        /** @psalm-suppress ImpureFunctionCall */
        $generator = ($this->values)(RegisterCleanup::noop());

        return (new Defer($generator))->match($wrap, $match, $empty);
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
        $values = $this->values;

        /** @var Implementation<array{T, S}> */
        return new self(
            static function(RegisterCleanup $register) use ($values, $sequence) {
                $otherRegister = $register->push();
                $self = $values($register);
                $other = self::open($sequence, $otherRegister);
                $self->rewind();
                $other->rewind();

                while ($self->valid()) {
                    $value = $self->current();

                    if (!$other->valid()) {
                        $register->pop();
                        $register->cleanup();

                        return;
                    }

                    yield [$value, $other->current()];
                    $self->next();
                    $other->next();
                }

                $otherRegister->cleanup();
            },
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
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $carry, $assert): \Generator {
                $iterator = $values($registerCleanup);
                $iterator->rewind();

                while ($iterator->valid()) {
                    /** @var T */
                    $value = $iterator->current();
                    $carry = $assert($carry, $value);

                    yield $value;

                    $iterator->next();
                }
            },
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
        $values = $this->values;

        return new self(static function(RegisterCleanup $registerCleanup) use ($values, $map, $exfiltrate) {
            $noop = RegisterCleanup::noop();
            $aggregate = new Aggregate($values($noop));
            /** @psalm-suppress MixedArgument */
            $values = $aggregate(static fn($a, $b) => self::open(
                $exfiltrate($map($a, $b)),
                $registerCleanup,
            ));
            $values->rewind();

            while ($values->valid()) {
                yield $values->current();
                $values->next();
            }
        });
    }

    /**
     * @return Implementation<T>
     */
    #[\Override]
    public function memoize(): Implementation
    {
        return $this->load();
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return self<T>
     */
    #[\Override]
    public function dropWhile(callable $condition): self
    {
        $values = $this->values;

        /** @psalm-suppress ImpureFunctionCall */
        return new self(static function(RegisterCleanup $registerCleanup) use ($values, $condition) {
            /** @psalm-suppress ImpureFunctionCall */
            $generator = $values($registerCleanup);
            $generator->rewind();

            /** @psalm-suppress ImpureMethodCall */
            while ($generator->valid()) {
                /**
                 * @psalm-suppress ImpureMethodCall
                 * @psalm-suppress ImpureFunctionCall
                 * @psalm-suppress PossiblyNullArgument
                 */
                if (!$condition($generator->current())) {
                    /** @psalm-suppress ImpureMethodCall */
                    yield $generator->current();
                    /** @psalm-suppress ImpureMethodCall */
                    $generator->next();

                    break;
                }

                /** @psalm-suppress ImpureMethodCall */
                $generator->next();
            }

            /** @psalm-suppress ImpureMethodCall */
            while ($generator->valid()) {
                /** @psalm-suppress ImpureMethodCall */
                yield $generator->current();
                /** @psalm-suppress ImpureMethodCall */
                $generator->next();
            }
        });
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return self<T>
     */
    #[\Override]
    public function takeWhile(callable $condition): self
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $register) use ($values, $condition): \Generator {
                // We intercept the registering of the cleanup function here
                // because this generator can be stopped when we reach the number
                // of elements to take so we have to cleanup here. In this case
                // the parent sequence may not need to cleanup as it could
                // iterate over the whole generator but this inner one still
                // needs to free resources correctly
                $middleware = $register->push();
                $iterator = $values($middleware);

                while ($iterator->valid()) {
                    /** @var T */
                    $value = $iterator->current();

                    if (!$condition($value)) {
                        $middleware->cleanup();
                        $register->pop();

                        return;
                    }

                    yield $value;
                    $iterator->next();
                }
            },
        );
    }

    /**
     * @return Implementation<T>
     */
    private function load(): Implementation
    {
        $values = [];
        $iterator = $this->iterator();
        /** @psalm-suppress ImpureMethodCall */
        $iterator->rewind();

        /** @psalm-suppress ImpureMethodCall */
        while ($iterator->valid()) {
            /** @psalm-suppress ImpureMethodCall */
            $values[] = $iterator->current();
            /** @psalm-suppress ImpureMethodCall */
            $iterator->next();
        }

        return new Primitive($values);
    }

    /**
     * @template A
     *
     * @param Implementation<A> $sequence
     *
     * @return \Iterator<A>
     */
    private static function open(
        Implementation $sequence,
        RegisterCleanup $registerCleanup,
    ): \Iterator {
        if ($sequence instanceof self) {
            return ($sequence->values)($registerCleanup);
        }

        return $sequence->iterator();
    }
}
