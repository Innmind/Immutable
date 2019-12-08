<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Set,
    Accumulate,
    ValidateArgument,
    Type,
    Exception\LogicException,
    Exception\CannotGroupEmptyStructure,
    Exception\ElementNotFound,
    Exception\OutOfBoundException,
};

/**
 * @template T
 */
final class Defer implements Implementation
{
    private string $type;
    /** @var \Iterator<T> */
    private \Iterator $values;
    private ValidateArgument $validate;

    public function __construct(string $type, \Generator $generator)
    {
        $this->type = $type;
        $this->values = new Accumulate($generator);
        $this->validate = Type::of($type);
    }

    public function type(): string
    {
        return $this->type;
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
     * @return \Iterator<T>
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

        /** @var T $value */
        foreach ($this->values as $value) {
            if ($index === $iteration) {
                /** @var T */
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
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            $this->type,
            (static function($values): \Generator {
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
            $this->type,
            (static function($values, $toDrop): \Generator {
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
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            $this->type,
            (static function($values, callable $predicate): \Generator {
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
     * @throws CannotGroupEmptyStructure
     *
     * @return Map<D, Sequence<T>>
     */
    public function groupBy(callable $discriminator): Map
    {
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
        /**
         * @psalm-suppress MissingClosureParamType
         * @var Implementation<int>
         */
        return new self(
            'int',
            (static function($values): \Generator {
                $index = 0;
                /** @var T $value */
                foreach ($values as $value) {
                    yield $index++;
                }
            })($this->values),
        );
    }

    /**
     * @param callable(T): T $function
     *
     * @return Implementation<T>
     */
    public function map(callable $function): Implementation
    {
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            $this->type,
            (static function($values, callable $map, ValidateArgument $validate): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    /** @var T */
                    $value = $map($value);
                    $validate($value, 1);

                    yield $value;
                }
            })($this->values, $function, $this->validate),
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
            $this->type,
            (static function($values, int $toPad, $element): \Generator {
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
        return $this->load()->partition($predicate);
    }

    /**
     * @return Implementation<T>
     */
    public function slice(int $from, int $until): Implementation
    {
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            $this->type,
            (static function($values, int $from, int $until): \Generator {
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
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            $this->type,
            (static function($values, int $size): \Generator {
                $taken = 0;
                /** @var T $value */
                foreach ($values as $value) {
                    if ($taken < $size) {
                        yield $value;
                    }

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
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            $this->type,
            (static function($values, Implementation $sequence): \Generator {
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
        return $this->filter(static function($value) use ($sequence): bool {
            /** @var T $value */
            return $sequence->contains($value);
        });
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
            $this->type,
            (static function($values, $element): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    yield $value;
                }

                yield $element;
            })($this->values, $element),
        );
    }

    /**
     * @param callable(T, T): int $function
     *
     * @return Implementation<T>
     */
    public function sort(callable $function): Implementation
    {
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            $this->type,
            (static function(\Iterator $values, callable $function): \Generator {
                $values = \iterator_to_array($values);
                \usort($values, $function);

                /** @var T $value */
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
        return new Primitive($this->type);
    }

    /**
     * @return Implementation<T>
     */
    public function reverse(): Implementation
    {
        /** @psalm-suppress MissingClosureParamType */
        return new self(
            $this->type,
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
     * @template ST
     *
     * @param null|callable(T): \Generator<ST> $mapper
     *
     * @return Sequence<ST>
     */
    public function toSequenceOf(string $type, callable $mapper = null): Sequence
    {
        /** @psalm-suppress MissingParamType */
        $mapper ??= static fn($v): \Generator => yield $v;

        /** @psalm-suppress MissingClosureParamType */
        return Sequence::defer(
            $type,
            (static function($values, callable $mapper): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    /** @var ST $newValue */
                    foreach ($mapper($value) as $newValue) {
                        yield $newValue;
                    }
                }
            })($this->values, $mapper),
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
        /** @psalm-suppress MissingParamType */
        $mapper ??= static fn($v): \Generator => yield $v;

        /** @psalm-suppress MissingClosureParamType */
        return Set::defer(
            $type,
            (static function($values, callable $mapper): \Generator {
                /** @var T $value */
                foreach ($values as $value) {
                    /** @var ST $newValue */
                    foreach ($mapper($value) as $newValue) {
                        yield $newValue;
                    }
                }
            })($this->values, $mapper),
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

    /**
     * @return Implementation<T>
     */
    private function load(): Implementation
    {
        return new Primitive(
            $this->type,
            ...\iterator_to_array($this->values),
        );
    }
}
