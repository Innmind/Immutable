<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Set,
    Type,
    Exception\LogicException,
    Exception\CannotGroupEmptyStructure,
    Exception\ElementNotFound,
    Exception\OutOfBoundException,
    Exception\NoElementMatchingPredicateFound,
};

/**
 * @template T
 */
final class Lazy implements Implementation
{
    /** @var \Closure(): \Generator<T> */
    private \Closure $values;
    private ?int $size = null;

    public function __construct(callable $generator)
    {
        /** @var \Closure(): \Generator<T> */
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

        /** @psalm-suppress MissingClosureParamType */
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
     * @return \Iterator<T>
     */
    public function iterator(): \Iterator
    {
        return ($this->values)();
    }

    /**
     * @throws OutOfBoundException
     *
     * @return T
     */
    public function get(int $index)
    {
        $iteration = 0;

        foreach ($this->iterator() as $value) {
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
        $values = $this->values;

        /** @psalm-suppress MissingClosureParamType */
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

        /** @psalm-suppress MissingClosureParamType */
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

        /** @psalm-suppress MissingClosureParamType */
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
     * @throws CannotGroupEmptyStructure
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
        foreach ($this->iterator() as $value) {
            return $value;
        }

        throw new OutOfBoundException;
    }

    /**
     * @return T
     */
    public function last()
    {
        foreach ($this->iterator() as $value) {
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
     * @throws ElementNotFound
     */
    public function indexOf($element): int
    {
        $index = 0;

        foreach ($this->iterator() as $value) {
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
        $values = $this->values;

        /**
         * @psalm-suppress MissingClosureParamType
         * @var Implementation<int>
         */
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
     * @param callable(T): T $function
     *
     * @return Implementation<T>
     */
    public function map(callable $function): Implementation
    {
        $values = $this->values;

        /** @psalm-suppress MissingClosureParamType */
        return new self(
            static function() use ($values, $function): \Generator {
                foreach ($values() as $value) {
                    $value = $function($value);

                    yield $value;
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

        /** @psalm-suppress MissingClosureParamType */
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

        /** @psalm-suppress MissingClosureParamType */
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
        $values = $this->values;

        /** @psalm-suppress MissingClosureParamType */
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

        /** @psalm-suppress MissingClosureParamType */
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
        $values = $this->values;

        /** @psalm-suppress MissingClosureParamType */
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

        /** @psalm-suppress MissingClosureParamType */
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
     * @template ST
     *
     * @param null|callable(T): \Generator<ST> $mapper
     *
     * @return Sequence<ST>
     */
    public function toSequenceOf(string $type, callable $mapper = null): Sequence
    {
        /** @psalm-suppress MissingClosureParamType */
        $mapper ??= static fn($v): \Generator => yield $v;
        $values = $this->values;

        /** @psalm-suppress MissingClosureParamType */
        return Sequence::lazy(
            static function() use ($values, $mapper): \Generator {
                foreach ($values() as $value) {
                    /** @var ST $newValue */
                    foreach ($mapper($value) as $newValue) {
                        yield $newValue;
                    }
                }
            },
        );
    }

    /**
     * @template ST
     *
     * @param null|callable(T): \Generator<ST> $mapper
     *
     * @return Set<ST>
     */
    public function toSetOf(string $type, callable $mapper = null): Set
    {
        /** @psalm-suppress MissingClosureParamType */
        $mapper ??= static fn($v): \Generator => yield $v;
        $values = $this->values;

        /** @psalm-suppress MissingClosureParamType */
        return Set::lazy(
            static function() use ($values, $mapper): \Generator {
                foreach ($values() as $value) {
                    /** @var ST $newValue */
                    foreach ($mapper($value) as $newValue) {
                        yield $newValue;
                    }
                }
            },
        );
    }

    /**
     * @template MT
     * @template MS
     *
     * @param callable(T): \Generator<MT, MS> $mapper
     *
     * @return Map<MT, MS>
     */
    public function toMapOf(string $key, string $value, callable $mapper): Map
    {
        return $this->load()->toMapOf($key, $value, $mapper);
    }

    public function find(callable $predicate)
    {
        foreach ($this->iterator() as $value) {
            if ($predicate($value) === true) {
                return $value;
            }
        }

        throw new NoElementMatchingPredicateFound;
    }

    /**
     * @return Implementation<T>
     */
    private function load(): Implementation
    {
        return new Primitive(...\iterator_to_array($this->iterator()));
    }
}
