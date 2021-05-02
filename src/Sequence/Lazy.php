<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Set,
    Maybe,
};

/**
 * @template T
 */
final class Lazy implements Implementation
{
    /** @var \Closure(): \Generator<int, T> */
    private \Closure $values;
    private ?int $size = null;

    public function __construct(callable $generator)
    {
        /** @var \Closure(): \Generator<int, T> */
        $this->values = \Closure::fromCallable(static function() use ($generator): \Generator {
            /** @var T $value */
            foreach ($generator() as $value) {
                yield $value;
            }
        });
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
            static function() use ($values, $element): \Generator {
                foreach ($values() as $value) {
                    yield $value;
                }

                yield $element;
            },
        );
    }

    public function size(): int
    {
        if (\is_int($this->size)) {
            return $this->size;
        }

        $size = 0;

        foreach ($this->iterator() as $_) {
            ++$size;
        }

        return $this->size = $size;
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
        return ($this->values)();
    }

    /**
     * @return Maybe<T>
     */
    public function get(int $index): Maybe
    {
        $iteration = 0;

        foreach ($this->iterator() as $value) {
            if ($index === $iteration) {
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
            static function() use ($values): \Generator {
                /** @var list<T> */
                $uniques = [];

                foreach ($values() as $value) {
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
            static function() use ($values, $size): \Generator {
                $dropped = 0;

                foreach ($values() as $value) {
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
            static function() use ($values, $predicate): \Generator {
                foreach ($values() as $value) {
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
    public function foreach(callable $function): void
    {
        foreach ($this->iterator() as $value) {
            $function($value);
        }
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
        foreach ($this->iterator() as $value) {
            return Maybe::just($value);
        }

        return Maybe::nothing();
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
        foreach ($this->iterator() as $value) {
            if ($value === $element) {
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

        foreach ($this->iterator() as $value) {
            if ($value === $element) {
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
            static function() use ($values): \Generator {
                $index = 0;

                foreach ($values() as $_) {
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
            static function() use ($values, $function): \Generator {
                foreach ($values() as $value) {
                    yield $function($value);
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
            static function() use ($values, $size, $element): \Generator {
                foreach ($values() as $value) {
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
            static function() use ($values, $from, $until): \Generator {
                $index = 0;

                foreach ($values() as $value) {
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
            static function() use ($values, $size): \Generator {
                $taken = 0;

                foreach ($values() as $value) {
                    if ($taken >= $size) {
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
            static function() use ($values, $sequence): \Generator {
                foreach ($values() as $value) {
                    yield $value;
                }

                foreach ($sequence->iterator() as $value) {
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
                $values = \iterator_to_array($values());
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
                $values = \iterator_to_array($values());

                yield from \array_reverse($values);
            },
        );
    }

    public function empty(): bool
    {
        return !$this->iterator()->valid();
    }

    /**
     * @return Sequence<T>
     */
    public function toSequence(): Sequence
    {
        $values = $this->values;

        return Sequence::lazy(
            static function() use ($values): \Generator {
                foreach ($values() as $value) {
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
            static function() use ($values): \Generator {
                foreach ($values() as $value) {
                    yield $value;
                }
            },
        );
    }

    public function find(callable $predicate): Maybe
    {
        foreach ($this->iterator() as $value) {
            if ($predicate($value) === true) {
                return Maybe::just($value);
            }
        }

        /** @var Maybe<T> */
        return Maybe::nothing();
    }

    /**
     * @return Implementation<T>
     */
    private function load(): Implementation
    {
        return new Primitive(...\iterator_to_array($this->iterator()));
    }
}
