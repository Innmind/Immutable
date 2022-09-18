<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Set,
    Maybe,
    SideEffect,
};

/**
 * @template T
 * @psalm-immutable
 * @psalm-type RegisterCleanup = callable(callable(): void): void
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
            static function(callable $registerCleanup) use ($values, $element): \Generator {
                /** @var RegisterCleanup $registerCleanup */
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
        return ($this->values)(self::bypassCleanup());
    }

    /**
     * @return Maybe<T>
     */
    public function get(int $index): Maybe
    {
        $iteration = 0;
        $cleanup = self::noCleanup();
        $generator = ($this->values)(static function(callable $userDefinedCleanup) use (&$cleanup) {
            $cleanup = $userDefinedCleanup;
        });

        foreach ($generator as $value) {
            if ($index === $iteration) {
                /** @psalm-suppress MixedFunctionCall Due to the reference in the closure above */
                $cleanup();

                return Maybe::just($value);
            }

            ++$iteration;
        }

        /** @var Maybe<T> */
        return Maybe::nothing();
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
            static function(callable $registerCleanup) use ($values): \Generator {
                /** @var list<T> */
                $uniques = [];

                /** @var RegisterCleanup $registerCleanup */
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
            static function(callable $registerCleanup) use ($values, $size): \Generator {
                /** @var RegisterCleanup $registerCleanup */
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
            static function(callable $registerCleanup) use ($values, $predicate): \Generator {
                /** @var RegisterCleanup $registerCleanup */
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
        foreach ($this->iterator() as $value) {
        }

        if (!isset($value)) {
            /** @var Maybe<T> */
            return Maybe::nothing();
        }

        return Maybe::just($value);
    }

    /**
     * @param T $element
     */
    public function contains($element): bool
    {
        $cleanup = self::noCleanup();
        $generator = ($this->values)(static function(callable $userDefinedCleanup) use (&$cleanup) {
            $cleanup = $userDefinedCleanup;
        });

        foreach ($generator as $value) {
            if ($value === $element) {
                /** @psalm-suppress MixedFunctionCall Due to the reference in the closure above */
                $cleanup();

                return true;
            }
        }

        return false;
    }

    /**
     * @param T $element
     *
     * @return Maybe<int>
     */
    public function indexOf($element): Maybe
    {
        $index = 0;
        $cleanup = self::noCleanup();
        $generator = ($this->values)(static function(callable $userDefinedCleanup) use (&$cleanup) {
            $cleanup = $userDefinedCleanup;
        });

        foreach ($generator as $value) {
            if ($value === $element) {
                /** @psalm-suppress MixedFunctionCall Due to the reference in the closure above */
                $cleanup();

                return Maybe::just($index);
            }

            ++$index;
        }

        /** @var Maybe<int> */
        return Maybe::nothing();
    }

    /**
     * Return the list of indices
     *
     * @return Implementation<int>
     */
    public function indices(): Implementation
    {
        $values = $this->values;

        /** @var Implementation<int> */
        return new self(
            static function(callable $registerCleanup) use ($values): \Generator {
                /** @var RegisterCleanup $registerCleanup */
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
            static function(callable $registerCleanup) use ($values, $function): \Generator {
                /** @var RegisterCleanup $registerCleanup */
                foreach ($values($registerCleanup) as $value) {
                    yield $function($value);
                }
            },
        );
    }

    /**
     * @template S
     *
     * @param callable(T): Sequence<S> $map
     * @param callable(Sequence<S>): Implementation<S> $exfiltrate
     *
     * @return Sequence<S>
     */
    public function flatMap(callable $map, callable $exfiltrate): Sequence
    {
        $values = $this->values;

        return Sequence::lazy(
            static function(callable $registerCleanup) use ($values, $map, $exfiltrate): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    $generator = self::open(
                        $exfiltrate($map($value)),
                        $registerCleanup,
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
            static function(callable $registerCleanup) use ($values, $size, $element): \Generator {
                /** @var RegisterCleanup $registerCleanup */
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
        $values = $this->values;

        return new self(
            static function(callable $registerCleanup) use ($values, $from, $until): \Generator {
                $index = 0;

                /** @var RegisterCleanup $registerCleanup */
                foreach ($values($registerCleanup) as $value) {
                    if ($index >= $from && $index < $until) {
                        yield $value;
                    }

                    ++$index;
                }
            },
        );
    }

    /**
     * @return Implementation<T>
     */
    public function take(int $size): Implementation
    {
        $values = $this->values;

        return new self(
            static function(callable $registerCleanup) use ($values, $size): \Generator {
                $taken = 0;
                // We intercept the registering of the cleanup function here
                // because this generator can be stopped when we reach the number
                // of elements to take so we have to cleanup here. In this case
                // the parent sequence may not need to cleanup as it could
                // iterate over the whole generator but this inner one still
                // needs to free resources correctly
                $cleanup = self::noCleanup();
                $middleware = static function(callable $userDefinedCleanup) use (&$cleanup, $registerCleanup): void {
                    $cleanup = $userDefinedCleanup;
                    $registerCleanup($userDefinedCleanup);
                };

                foreach ($values($middleware) as $value) {
                    if ($taken >= $size) {
                        /** @psalm-suppress MixedFunctionCall Due to the reference in the closure above */
                        $cleanup();

                        return;
                    }

                    yield $value;
                    ++$taken;
                }
            },
        );
    }

    /**
     * @return Implementation<T>
     */
    public function takeEnd(int $size): Implementation
    {
        // this cannot be optimised as the whole generator needs to be loaded
        // in order to know the elements to drop
        return $this->load()->takeEnd($size);
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
            static function(callable $registerCleanup) use ($values, $sequence): \Generator {
                /** @var RegisterCleanup $registerCleanup */
                foreach ($values($registerCleanup) as $value) {
                    yield $value;
                }

                foreach (self::open($sequence, $registerCleanup) as $value) {
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
                // bypass the registering of cleanup function as we iterate over
                // the whole generator
                $values = \iterator_to_array($values(self::bypassCleanup()));
                \usort($values, $function);

                foreach ($values as $value) {
                    yield $value;
                }
            },
        );
    }

    /**
     * @template R
     * @param R $carry
     * @param callable(R, T): R $reducer
     *
     * @return R
     */
    public function reduce($carry, callable $reducer)
    {
        foreach ($this->iterator() as $value) {
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
                // bypass the registering of cleanup function as we iterate over
                // the whole generator
                $values = \iterator_to_array($values(self::bypassCleanup()));

                yield from \array_reverse($values);
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
            static function(callable $registerCleanup) use ($values): \Generator {
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
            static function(callable $registerCleanup) use ($values): \Generator {
                foreach ($values($registerCleanup) as $value) {
                    yield $value;
                }
            },
        );
    }

    public function find(callable $predicate): Maybe
    {
        $cleanup = self::noCleanup();
        $generator = ($this->values)(static function(callable $userDefinedCleanup) use (&$cleanup) {
            $cleanup = $userDefinedCleanup;
        });

        foreach ($generator as $value) {
            if ($predicate($value) === true) {
                /** @psalm-suppress MixedFunctionCall Due to the reference in the closure above */
                $cleanup();

                return Maybe::just($value);
            }
        }

        /** @var Maybe<T> */
        return Maybe::nothing();
    }

    public function match(callable $wrap, callable $match, callable $empty)
    {
        $generator = ($this->values)(self::bypassCleanup());

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
            static function(callable $registerCleanup) use ($values, $sequence) {
                /** @var RegisterCleanup $registerCleanup */
                $other = self::open($sequence, $registerCleanup);

                foreach ($values($registerCleanup) as $value) {
                    if (!$other->valid()) {
                        return;
                    }

                    yield [$value, $other->current()];
                    $other->next();
                }
            },
        );
    }

    /**
     * @return Implementation<T>
     */
    private function load(): Implementation
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new Primitive(\array_values(\iterator_to_array($this->iterator())));
    }

    /**
     * @param RegisterCleanup $registerCleanup
     *
     * @return \Iterator<int, T>
     */
    private static function open(
        Implementation $sequence,
        callable $registerCleanup,
    ): \Iterator {
        if ($sequence instanceof self) {
            return ($sequence->values)($registerCleanup);
        }

        return $sequence->iterator();
    }

    /**
     * @psalm-pure
     *
     * @return callable(): void
     */
    private static function noCleanup(): callable
    {
        return static function(): void {
            // nothing to do
        };
    }

    /**
     * @psalm-pure
     *
     * @return RegisterCleanup
     */
    private static function bypassCleanup(): callable
    {
        return static function(): void {
            // nothing to do
        };
    }
}
