<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Set,
    Maybe,
    Accumulate,
    SideEffect,
};

/**
 * @template T
 * @implements Implementation<T>
 * @psalm-immutable
 */
final class Defer implements Implementation
{
    /** @var \Iterator<int, T> */
    private \Iterator $values;

    public function __construct(\Generator $generator)
    {
        /**
         * @psalm-suppress ImpureFunctionCall
         * @var \Iterator<int, T>
         */
        $this->values = new Accumulate((static function(\Generator $generator): \Generator {
            /** @var T $value */
            foreach ($generator as $value) {
                yield $value;
            }
        })($generator));
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    public function __invoke($element): Implementation
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, mixed $element): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    yield $value;
                }

                yield $element;
            })($this->values, $element),
        );
    }

    public function size(): int
    {
        return $this->load()->size();
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
        return $this->values;
    }

    /**
     * @return Maybe<T>
     */
    public function get(int $index): Maybe
    {
        return Maybe::defer(function() use ($index) {
            $iteration = 0;

            foreach ($this->values as $value) {
                if ($index === $iteration) {
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values): \Generator {
                /** @var list<T> */
                $uniques = [];

                /** @var T $value */
                foreach ($values as $value) {
                    if (!\in_array($value, $uniques, true)) {
                        $uniques[] = $value;

                        yield $value;
                    }
                }
            })($this->values),
        );
    }

    /**
     * @return Implementation<T>
     */
    public function drop(int $size): Implementation
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, int $toDrop): \Generator {
                $dropped = 0;

                /** @var T $value */
                foreach ($values as $value) {
                    if ($dropped < $toDrop) {
                        ++$dropped;

                        continue;
                    }

                    yield $value;
                }
            })($this->values, $size),
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, callable $predicate): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    if ($predicate($value)) {
                        yield $value;
                    }
                }
            })($this->values, $predicate),
        );
    }

    /**
     * @param callable(T): void $function
     */
    public function foreach(callable $function): SideEffect
    {
        foreach ($this->values as $value) {
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
        return Maybe::defer(function() {
            foreach ($this->values as $value) {
                return Maybe::just($value);
            }

            /** @var Maybe<T> */
            return Maybe::nothing();
        });
    }

    /**
     * @return Maybe<T>
     */
    public function last(): Maybe
    {
        return Maybe::defer(function() {
            $loaded = false;

            foreach ($this->values as $value) {
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
        foreach ($this->values as $value) {
            if ($value === $element) {
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

            foreach ($this->values as $value) {
                if ($value === $element) {
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
        /**
         * @psalm-suppress ImpureFunctionCall
         * @var Implementation<0|positive-int>
         */
        return new self(
            (static function(\Iterator $values): \Generator {
                $index = 0;

                foreach ($values as $_) {
                    yield $index++;
                }
            })($this->values),
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, callable $map): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    yield $map($value);
                }
            })($this->values, $function),
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, callable $map, callable $exfiltrate): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    /**
                     * @var callable(T): C $map
                     * @var callable(C): Implementation<S> $exfiltrate
                     */
                    foreach ($exfiltrate($map($value))->iterator() as $inner) {
                        yield $inner;
                    }
                }
            })($this->values, $map, $exfiltrate),
        );
    }

    /**
     * @param T $element
     *
     * @return Implementation<T>
     */
    public function pad(int $size, $element): Implementation
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, int $toPad, mixed $element): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    yield $value;
                    --$toPad;
                }

                while ($toPad > 0) {
                    yield $element;
                    --$toPad;
                }
            })($this->values, $size, $element),
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, int $from, int $until): \Generator {
                $index = 0;
                /** @var T $value */
                foreach ($values as $value) {
                    if ($index >= $from && $index < $until) {
                        yield $value;
                    }

                    ++$index;
                }
            })($this->values, $from, $until),
        );
    }

    /**
     * @return Implementation<T>
     */
    public function take(int $size): Implementation
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, int $size): \Generator {
                $taken = 0;
                /** @var T $value */
                foreach ($values as $value) {
                    if ($taken >= $size) {
                        return;
                    }

                    yield $value;
                    ++$taken;
                }
            })($this->values, $size),
        );
    }

    /**
     * @param 0|positive-int $size
     *
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, Implementation $sequence): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    yield $value;
                }

                /** @var T $value */
                foreach ($sequence->iterator() as $value) {
                    yield $value;
                }
            })($this->values, $sequence),
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, callable $function): \Generator {
                /** @var callable(T, T): int $sorter */
                $sorter = $function;

                /** @var list<T> */
                $values = \iterator_to_array($values);
                \usort($values, $sorter);

                foreach ($values as $value) {
                    yield $value;
                }
            })($this->values, $function),
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
        foreach ($this->values as $value) {
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values): \Generator {
                $values = \iterator_to_array($values);

                yield from \array_reverse($values);
            })($this->values),
        );
    }

    public function empty(): bool
    {
        /** @psalm-suppress ImpureMethodCall */
        $this->values->rewind();

        /** @psalm-suppress ImpureMethodCall */
        return !$this->values->valid();
    }

    /**
     * @return Sequence<T>
     */
    public function toSequence(): Sequence
    {
        /** @psalm-suppress ImpureFunctionCall */
        return Sequence::defer(
            (static function(\Iterator $values): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    yield $value;
                }
            })($this->values),
        );
    }

    /**
     * @return Set<T>
     */
    public function toSet(): Set
    {
        /** @psalm-suppress ImpureFunctionCall */
        return Set::defer(
            (static function(\Iterator $values): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    yield $value;
                }
            })($this->values),
        );
    }

    public function find(callable $predicate): Maybe
    {
        return Maybe::defer(function() use ($predicate) {
            foreach ($this->values as $value) {
                /** @psalm-suppress ImpureFunctionCall */
                if ($predicate($value) === true) {
                    return Maybe::just($value);
                }
            }

            /** @var Maybe<T> */
            return Maybe::nothing();
        });
    }

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
    public function zip(Implementation $sequence): Implementation
    {
        /**
         * @psalm-suppress ImpureFunctionCall
         * @var Implementation<array{T, S}>
         */
        return new self(
            (static function(\Iterator $self, \Iterator $other) {
                /** @var \Iterator<T> $self */
                foreach ($self as $value) {
                    if (!$other->valid()) {
                        return;
                    }

                    yield [$value, $other->current()];
                    $other->next();
                }
            })($this->values, $sequence->iterator()),
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
        /** @psalm-suppress ImpureFunctionCall */
        return new self(
            (static function(\Iterator $values, mixed $carry, callable $assert): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    /** @var R */
                    $carry = $assert($carry, $value);

                    yield $value;
                }
            })($this->values, $carry, $assert),
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
        $aggregate = new Aggregate($this->iterator());

        /** @psalm-suppress MixedArgument */
        return new self($aggregate(static fn($a, $b) => $exfiltrate($map($a, $b))->iterator()));
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
        return new self((static function(\Iterator $values, callable $condition) {
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
        })($this->values, $condition));
    }

    /**
     * @return Implementation<T>
     */
    private function load(): Implementation
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new Primitive(\array_values(\iterator_to_array($this->values)));
    }
}
