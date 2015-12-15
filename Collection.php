<?php

namespace Innmind\Immutable;

class Collection implements CollectionInterface
{
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
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
    public function filter(callable $filter = null)
    {
        if ($filter === null) {
            $values = array_filter($this->values);
        } else {
            $values = array_filter($this->values, $filter);
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function intersect(CollectionInterface $collection)
    {
        return new self(array_intersect(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function chunk($size)
    {
        return new self(array_chunk($this->values, (int) $size));
    }

    /**
     * {@inheritdoc}
     */
    public function shift()
    {
        $values = $this->values;
        array_shift($values);

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce(callable $reducer, $initial = null)
    {
        return array_reduce($this->values, $reducer, $initial);
    }

    /**
     * {@inheritdoc}
     */
    public function search($needle, $strict = true)
    {
        return array_search($needle, $this->values, $strict);
    }

    /**
     * {@inheritdoc}
     */
    public function uintersect(CollectionInterface $collection, callable $intersecter)
    {
        return new self(array_uintersect(
            $this->values,
            $collection->toPrimitive(),
            $intersecter
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function keyIntersect(CollectionInterface $collection)
    {
        return new self(array_intersect_key(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $mapper)
    {
        return new self(array_map($mapper, $this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function pad($size, $value)
    {
        return new self(array_pad($this->values, (int) $size, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function pop()
    {
        $values = $this->values;
        array_pop($values);

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function sum()
    {
        return array_sum($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function diff(CollectionInterface $collection)
    {
        return new self(array_diff($this->values, $collection->toPrimitive()));
    }

    /**
     * {@inheritdoc}
     */
    public function flip()
    {
        return new self(array_flip($this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function keys($search = null, $strict = true)
    {
        return new self(array_keys($this->values, $search, $strict));
    }
}
