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
};

/**
 * @template T
 * @implements Implementation<T>
 * @psalm-immutable
 */
final class Lazy implements Implementation
{
    /** @var \Closure(RegisterCleanup): \Generator<int, T> */
    private \Closure $values;

    /**
     * @param callable(RegisterCleanup): \Generator<T> $generator
     */
    public function __construct(callable $generator)
    {
        /** @var \Closure(RegisterCleanup): \Generator<int, T> */
        $this->values = \Closure::fromCallable($generator);
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    public function __invoke($element): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $element): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    yield $value;
                }

                yield $element;
            },
        );
    }

    public function size(): int
    {
        $size = 0;

        foreach ($this->iterator() as $_) {
            ++$size;
        }

        return $size;
    }

    public function count(): int
    {
        return $this->size();
    }

    /**
     * @return \Iterator<int, T>
     */
    public function iterator(): \Iterator
    {
        // when accessing the iterator from the outside we cannot know when it
        // will be stopped being iterated over so we can't have a way to notify
        // the generator to cleanup its ressources, so we pass an empty function
        // that does nothing
        /** @psalm-suppress ImpureFunctionCall */
        return ($this->values)(RegisterCleanup::noop());
    }

    /**
     * @return Maybe<T>
     */
    public function get(int $index): Maybe
    {
        return Maybe::defer(function() use ($index) {
            $iteration = 0;
            $register = RegisterCleanup::noop();
            $generator = ($this->values)($register);

            foreach ($generator as $value) {
                if ($index === $iteration) {
                    $register->cleanup();

                    return Maybe::just($value);
                }

                ++$iteration;
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
    public function distinct(): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values): \Generator {
                /** @var list<T> */
                $uniques = [];

                foreach ($values($registerCleanup) as $value) {
                    if (!\in_array($value, $uniques, true)) {
                        $uniques[] = $value;

                        yield $value;
                    }
                }
            },
        );
    }

    /**
     * @return Implementation<T>
     */
    public function drop(int $size): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $size): \Generator {
                $dropped = 0;

                foreach ($values($registerCleanup) as $value) {
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
    public function dropEnd(int $size): Implementation
    {
        // this cannot be optimised as the whole generator needs to be loaded
        // in order to know the elements to drop
        return $this->load()->dropEnd($size);
    }

    /**
     * @param Implementation<T> $sequence
     */
    public function equals(Implementation $sequence): bool
    {
        return $this->load()->equals($sequence);
    }

    /**
     * @param callable(T): bool $predicate
     *
     * @return Implementation<T>
     */
    public function filter(callable $predicate): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $predicate): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    if ($predicate($value)) {
                        yield $value;
                    }
                }
            },
        );
    }

    /**
     * @param callable(T): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        foreach ($this->iterator() as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            $function($value);
        }

        return new SideEffect;
    }

    /**
     * @template D
     * @param callable(T): D $discriminator
     *
     * @return Map<D, Sequence<T>>
     */
    public function groupBy(callable $discriminator): Map
    {
        /** @var Map<D, Sequence<T>> */
        return $this->load()->groupBy($discriminator);
    }

    /**
     * @return Maybe<T>
     */
    public function first(): Maybe
    {
        return $this->get(0);
    }

    /**
     * @return Maybe<T>
     */
    public function last(): Maybe
    {
        return Maybe::defer(function() {
            $loaded = false;

            foreach ($this->iterator() as $value) {
                $loaded = true;
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
    public function contains($element): bool
    {
        $register = RegisterCleanup::noop();
        /** @psalm-suppress ImpureFunctionCall */
        $generator = ($this->values)($register);

        /** @psalm-suppress ImpureMethodCall */
        foreach ($generator as $value) {
            if ($value === $element) {
                /** @psalm-suppress ImpureMethodCall */
                $register->cleanup();

                return true;
            }
        }

        return false;
    }

    /**
     * @param T $element
     *
     * @return Maybe<0|positive-int>
     */
    public function indexOf($element): Maybe
    {
        return Maybe::defer(function() use ($element) {
            $index = 0;
            $register = RegisterCleanup::noop();
            $generator = ($this->values)($register);

            foreach ($generator as $value) {
                if ($value === $element) {
                    $register->cleanup();

                    /** @var Maybe<0|positive-int> */
                    return Maybe::just($index);
                }

                ++$index;
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
    public function indices(): Implementation
    {
        $values = $this->values;

        /** @var Implementation<0|positive-int> */
        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values): \Generator {
                $index = 0;

                foreach ($values($registerCleanup) as $_) {
                    yield $index++;
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
    public function map(callable $function): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $function): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    yield $function($value);
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
    public function flatMap(callable $map, callable $exfiltrate): self
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $map, $exfiltrate): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    $generator = self::open(
                        $exfiltrate($map($value)),
                        $registerCleanup->push(),
                    );

                    foreach ($generator as $inner) {
                        yield $inner;
                    }
                }
            },
        );
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    public function pad(int $size, $element): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $size, $element): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    yield $value;
                    --$size;
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
    public function partition(callable $predicate): Map
    {
        /** @var Map<bool, Sequence<T>> */
        return $this->load()->partition($predicate);
    }

    /**
     * @return Implementation<T>
     */
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
    public function take(int $size): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $register) use ($values, $size): \Generator {
                $taken = 0;
                // We intercept the registering of the cleanup function here
                // because this generator can be stopped when we reach the number
                // of elements to take so we have to cleanup here. In this case
                // the parent sequence may not need to cleanup as it could
                // iterate over the whole generator but this inner one still
                // needs to free resources correctly
                $middleware = $register->push();

                foreach ($values($middleware) as $value) {
                    if ($taken >= $size) {
                        $middleware->cleanup();
                        $register->pop();

                        return;
                    }

                    yield $value;
                    ++$taken;
                }
            },
        );
    }

    /**
     * @param 0|positive-int $size
     *
     * @return Implementation<T>
     */
    public function takeEnd(int $size): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $register) use ($values, $size): \Generator {
                $buffer = [];
                $count = 0;

                foreach ($values($register) as $value) {
                    $buffer[] = $value;
                    ++$count;

                    if ($count > $size) {
                        \array_shift($buffer);
                    }
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
    public function append(Implementation $sequence): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $sequence): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    yield $value;
                }

                /** @var \Iterator<int, T> */
                $generator = self::open($sequence, $registerCleanup);

                foreach ($generator as $value) {
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
    public function prepend(Implementation $sequence): Implementation
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $sequence): \Generator {
                /** @var \Iterator<int, T> */
                $generator = self::open($sequence, $registerCleanup);

                foreach ($generator as $value) {
                    yield $value;
                }

                foreach ($values($registerCleanup) as $value) {
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
    public function sort(callable $function): Implementation
    {
        $values = $this->values;

        return new self(
            static function() use ($values, $function): \Generator {
                $loaded = [];

                // bypass the registering of cleanup function as we iterate over
                // the whole generator
                foreach ($values(RegisterCleanup::noop()) as $value) {
                    $loaded[] = $value;
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
    public function reduce($carry, callable $reducer)
    {
        foreach ($this->iterator() as $value) {
            /** @psalm-suppress ImpureFunctionCall */
            $carry = $reducer($carry, $value);
        }

        return $carry;
    }

    /**
     * @return Implementation<T>
     */
    public function clear(): Implementation
    {
        return new Primitive;
    }

    /**
     * @return Implementation<T>
     */
    public function reverse(): Implementation
    {
        $values = $this->values;

        return new self(
            static function() use ($values): \Generator {
                $reversed = [];

                // bypass the registering of cleanup function as we iterate over
                // the whole generator
                foreach ($values(RegisterCleanup::noop()) as $value) {
                    \array_unshift($reversed, $value);
                }

                foreach ($reversed as $value) {
                    yield $value;
                }
            },
        );
    }

    public function empty(): bool
    {
        /** @psalm-suppress ImpureMethodCall */
        return !$this->iterator()->valid();
    }

    /**
     * @return Sequence<T>
     */
    public function toSequence(): Sequence
    {
        $values = $this->values;

        return Sequence::lazy(
            static function(RegisterCleanup $registerCleanup) use ($values): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    yield $value;
                }
            },
        );
    }

    /**
     * @return Set<T>
     */
    public function toSet(): Set
    {
        $values = $this->values;

        return Set::lazy(
            static function(RegisterCleanup $registerCleanup) use ($values): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    yield $value;
                }
            },
        );
    }

    public function find(callable $predicate): Maybe
    {
        return Maybe::defer(function() use ($predicate) {
            $register = RegisterCleanup::noop();
            $generator = ($this->values)($register);

            foreach ($generator as $value) {
                /** @psalm-suppress ImpureFunctionCall */
                if ($predicate($value) === true) {
                    $register->cleanup();

                    return Maybe::just($value);
                }
            }

            /** @var Maybe<T> */
            return Maybe::nothing();
        });
    }

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
    public function zip(Implementation $sequence): Implementation
    {
        $values = $this->values;

        /** @var Implementation<array{T, S}> */
        return new self(
            static function(RegisterCleanup $register) use ($values, $sequence) {
                $otherRegister = $register->push();
                $other = self::open($sequence, $otherRegister);

                foreach ($values($register) as $value) {
                    if (!$other->valid()) {
                        $register->pop();
                        $register->cleanup();

                        return;
                    }

                    yield [$value, $other->current()];
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
    public function safeguard($carry, callable $assert): self
    {
        $values = $this->values;

        return new self(
            static function(RegisterCleanup $registerCleanup) use ($values, $carry, $assert): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    $carry = $assert($carry, $value);

                    yield $value;
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
    public function aggregate(callable $map, callable $exfiltrate): self
    {
        return new self(function(RegisterCleanup $registerCleanup) use ($map, $exfiltrate) {
            $aggregate = new Aggregate($this->iterator());
            /** @psalm-suppress MixedArgument */
            $values = $aggregate(static fn($a, $b) => self::open(
                $exfiltrate($map($a, $b)),
                $registerCleanup,
            ));

            foreach ($values as $value) {
                yield $value;
            }
        });
    }

    /**
     * @return Implementation<T>
     */
    public function memoize(): Implementation
    {
        return $this->load();
    }

    /**
     * @param callable(T): bool $condition
     *
     * @return self<T>
     */
    public function dropWhile(callable $condition): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(function(RegisterCleanup $registerCleanup) use ($condition) {
            /** @psalm-suppress ImpureFunctionCall */
            $generator = ($this->values)($registerCleanup);

            /** @psalm-suppress ImpureMethodCall */
            while ($generator->valid()) {
                /**
                 * @psalm-suppress ImpureMethodCall
                 * @psalm-suppress ImpureFunctionCall
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

                foreach ($values($middleware) as $value) {
                    if (!$condition($value)) {
                        $middleware->cleanup();
                        $register->pop();

                        return;
                    }

                    yield $value;
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

        foreach ($this->iterator() as $value) {
            $values[] = $value;
        }

        return new Primitive($values);
    }

    /**
     * @template A
     *
     * @param Implementation<A> $sequence
     *
     * @return \Iterator<int, A>
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
