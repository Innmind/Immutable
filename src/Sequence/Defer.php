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
    Exception\ElementNotFound,
    Exception\OutOfBoundException,
};

/**
 * @template T
 */
final class Defer implements Implementation
{
    /** @var \Iterator<int, T> */
    private \Iterator $values;

    public function __construct(\Generator $generator)
    {
        /** @var \Iterator<int, T> */
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
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            (static function(\Iterator $values, $element): \Generator {
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
        return $this->load()->count();
    }

    /**
     * @return \Iterator<int, T>
     */
    public function iterator(): \Iterator
    {
        return $this->values;
    }

    /**
     * @throws OutOfBoundException
     *
     * @return T
     */
    public function get(int $index)
    {
        $iteration = 0;

        foreach ($this->values as $value) {
            if ($index === $iteration) {
                return $value;
            }

            ++$iteration;
        }

        throw new OutOfBoundException;
    }

    /**
     * @param Implementation<T> $sequence
     *
     * @return Implementation<T>
     */
    public function diff(Implementation $sequence): Implementation
    {
        /** @psalm-suppress MissingClosureParamType */
        return $this->filter(static function($value) use ($sequence): bool {
            /** @var T $value */
            return !$sequence->contains($value);
        });
    }

    /**
     * @return Implementation<T>
     */
    public function distinct(): Implementation
    {
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
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            (static function(\Iterator $values, $toDrop): \Generator {
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
    public function foreach(callable $function): void
    {
        foreach ($this->values as $value) {
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
     * @return T
     */
    public function first()
    {
        foreach ($this->values as $value) {
            return $value;
        }

        throw new OutOfBoundException;
    }

    /**
     * @return T
     */
    public function last()
    {
        foreach ($this->values as $value) {
        }

        if (!isset($value)) {
            throw new OutOfBoundException;
        }

        /** @var T */
        return $value;
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
     * @throws ElementNotFound
     */
    public function indexOf($element): int
    {
        $index = 0;

        foreach ($this->values as $value) {
            if ($value === $element) {
                return $index;
            }

            ++$index;
        }

        throw new ElementNotFound($element);
    }

    /**
     * Return the list of indices
     *
     * @return Implementation<int>
     */
    public function indices(): Implementation
    {
        /** @var Implementation<int> */
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
     * @param T $element
     *
     * @return Implementation<T>
     */
    public function pad(int $size, $element): Implementation
    {
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            (static function(\Iterator $values, int $toPad, $element): \Generator {
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
     * @throws OutOfBoundException
     *
     * @return Sequence<Sequence<T>>
     */
    public function splitAt(int $position): Sequence
    {
        return $this->load()->splitAt($position);
    }

    /**
     * @return Implementation<T>
     */
    public function take(int $size): Implementation
    {
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
        /** @psalm-suppress MissingClosureParamType */
        return $this->filter(static function($value) use ($sequence): bool {
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
        return new self(
            (static function(\Iterator $values): \Generator {
                $values = \iterator_to_array($values);

                yield from \array_reverse($values);
            })($this->values),
        );
    }

    public function empty(): bool
    {
        $this->values->rewind();

        return !$this->values->valid();
    }

    /**
     * @return Sequence<T>
     */
    public function toSequence(): Sequence
    {
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
        foreach ($this->values as $value) {
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
        return new Primitive(...\iterator_to_array($this->values));
    }
}
