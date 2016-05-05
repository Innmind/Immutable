<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    Exception\OutOfBoundException,
    Exception\LogicException,
    Exception\ElementNotFoundException,
    Exception\GroupEmptySequenceException
};

/**
 * A defined set of ordered elements
 */
class Sequence implements SequenceInterface
{
    private $values;
    private $size;

    public function __construct(...$values)
    {
        $this->values = $values;
        $this->size = count($values);
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->size();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return current($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return key($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        next($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        reset($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->key() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new OutOfBoundException(sprintf(
                'Unknown index %s',
                $offset
            ));
        }

        return $this->values[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new LogicException('You can\'t modify a sequence');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('You can\'t modify a sequence');
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive()
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function get(int $index)
    {
        if ($index >= $this->size()) {
            throw new OutOfBoundException;
        }

        return $this->values[$index];
    }

    /**
     * {@inheritdoc}
     */
    public function diff(SequenceInterface $seq): SequenceInterface
    {
        return new self(
            ...array_diff(
                $this->values,
                $seq->toPrimitive()
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function distinct(): SequenceInterface
    {
        return new self(...array_unique($this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function drop(int $size): SequenceInterface
    {
        return new self(...array_slice($this->values, $size));
    }

    /**
     * {@inheritdoc}
     */
    public function dropEnd(int $size): SequenceInterface
    {
        return new self(...array_slice($this->values, 0, $this->size() - $size));
    }

    /**
     * {@inheritdoc}
     */
    public function equals(SequenceInterface $seq): bool
    {
        return $this->values === $seq->toPrimitive();
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): SequenceInterface
    {
        return new self(...array_filter(
            $this->values,
            $predicate
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): SequenceInterface
    {
        foreach ($this->values as $value) {
            $function($value);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): MapInterface
    {
        if ($this->size() === 0) {
            throw new GroupEmptySequenceException;
        }

        $map = null;

        foreach ($this->values as $value) {
            $key = $discriminator($value);

            if ($map === null) {
                $type = gettype($key);
                $map = new Map(
                    $type === 'object' ? get_class($key) : $type,
                    SequenceInterface::class
                );
            }

            if ($map->contains($key)) {
                $map = $map->put(
                    $key,
                    $map->get($key)->add($value)
                );
            } else {
                $map = $map->put($key, new self($value));
            }
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function first()
    {
        return $this->values[0];
    }

    /**
     * {@inheritdoc}
     */
    public function last()
    {
        return $this->values[$this->size() - 1];
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element): bool
    {
        return in_array($element, $this->values, true);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($element): int
    {
        $index = array_search($element, $this->values, true);

        if ($index === false) {
            throw new ElementNotFoundException;
        }

        return $index;
    }

    /**
     * {@inheritdoc}
     */
    public function indices(): SequenceInterface
    {
        return new self(...array_keys($this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): SequenceInterface
    {
        return new self(...array_map($function, $this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function pad(int $size, $element): SequenceInterface
    {
        return new self(...array_pad($this->values, $size, $element));
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): SequenceInterface
    {
        $truthy = [];
        $falsy = [];

        foreach ($this->values as $value) {
            if ($predicate($value) === true) {
                $truthy[] = $value;
            } else {
                $falsy[] = $value;
            }
        }

        return new self(
            new self(...$truthy),
            new self(...$falsy)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function slice(int $from, int $until): SequenceInterface
    {
        return new self(...array_slice(
            $this->values,
            $from,
            $until - $from
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function splitAt(int $index): SequenceInterface
    {
        return new self(
            $this->slice(0, $index),
            $this->slice($index, $this->size())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function take(int $size): SequenceInterface
    {
        return $this->slice(0, $size);
    }

    /**
     * {@inheritdoc}
     */
    public function takeEnd(int $size): SequenceInterface
    {
        return $this->slice($this->size() - $size, $this->size());
    }

    /**
     * {@inheritdoc}
     */
    public function append(SequenceInterface $seq): SequenceInterface
    {
        return new self(...$this->values, ...$seq->toPrimitive());
    }

    /**
     * {@inheritdoc}
     */
    public function intersect(SequenceInterface $seq): SequenceInterface
    {
        return new self(...array_intersect($this->values, $seq->toPrimitive()));
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): StringPrimitive
    {
        return new StringPrimitive(implode($separator, $this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function add($element): SequenceInterface
    {
        $values = $this->values;
        $values[] = $element;

        return new self(...$values);
    }

    /**
     * {@inheritdoc}
     */
    public function sort(callable $function): SequenceInterface
    {
        $values = $this->values;
        usort($values, $function);

        return new self(...$values);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        return array_reduce($this->values, $reducer, $carry);
    }
}
