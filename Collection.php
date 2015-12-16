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
        return array_search($needle, $this->values, (bool) $strict);
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
        return new self(array_keys($this->values, $search, (bool) $strict));
    }

    /**
     * {@inheritdoc}
     */
    public function push($value)
    {
        $values = $this->values;
        array_push($values, $value);

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function rand($num = 1)
    {
        if ((int) $num > $this->count()->toPrimitive()) {
            throw new OutOfBoundException(
                'Trying to return a wider collection than the current one'
            );
        }

        $keys = (array) array_rand($this->values, $num);
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->values[$key];
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(CollectionInterface $collection)
    {
        return new self(array_merge(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function slice($offset, $length = null, $preserveKeys = false)
    {
        return new self(array_slice(
            $this->values,
            (int) $offset,
            $length === null ? null : (int) $length,
            (bool) $preserveKeys
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function udiff(CollectionInterface $collection, callable $differ)
    {
        return new self(array_udiff(
            $this->values,
            $collection->toPrimitive(),
            $differ
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function column($key, $indexKey = null)
    {
        return new self(array_column(
            $this->values,
            $key,
            $indexKey
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function splice($offset, $length = 0, $replacement = [])
    {
        $values = $this->values;
        array_splice($values, (int) $offset, (int) $length, $replacement);

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function unique($flags = SORT_REGULAR)
    {
        return new self(array_unique($this->values, (int) $flags));
    }

    /**
     * {@inheritdoc}
     */
    public function values()
    {
        return new self(array_values($this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function product()
    {
        return array_product($this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function replace(CollectionInterface $collection)
    {
        return new self(array_replace(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function reverse($preserveKeys = false)
    {
        return new self(array_reverse($this->values, (bool) $preserveKeys));
    }

    /**
     * {@inheritdoc}
     */
    public function unshift($value)
    {
        $values = $this->values;
        array_unshift($values, $value);

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function keyDiff(CollectionInterface $collection)
    {
        return new self(array_diff_key(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function ukeyDiff(CollectionInterface $collection, callable $differ)
    {
        return new self(array_diff_ukey(
            $this->values,
            $collection->toPrimitive(),
            $differ
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function associativeDiff(CollectionInterface $collection)
    {
        return new self(array_diff_assoc(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function hasKey($key, $strict = true)
    {
        if ((bool) $strict === true) {
            $bool = array_key_exists($key, $this->values);
        } else {
            $bool = isset($this->values[$key]);
        }

        return new BooleanPrimitive($bool);
    }

    /**
     * {@inheritdoc}
     */
    public function countValues()
    {
        return new self(array_count_values($this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function ukeyIntersect(CollectionInterface $collection, callable $intersecter)
    {
        return new self(array_intersect_ukey(
            $this->values,
            $collection->toPrimitive(),
            $intersecter
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function associativeIntersect(CollectionInterface $collection)
    {
        return new self(array_intersect_assoc(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function sort($flags = SORT_REGULAR)
    {
        $values = $this->values;
        $bool = sort($values, (int) $flags);

        if ($bool === false) {
            throw new \Exception('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function associativeSort($flags = SORT_REGULAR)
    {
        $values = $this->values;
        $bool = asort($values, (int) $flags);

        if ($bool === false) {
            throw new \Exception('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function keySort($flags = SORT_REGULAR)
    {
        $values = $this->values;
        $bool = ksort($values, (int) $flags);

        if ($bool === false) {
            throw new \Exception('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function ukeySort(callable $sorter)
    {
        $values = $this->values;
        $bool = uksort($values, $sorter);

        if ($bool === false) {
            throw new \Exception('Sort failure');
        }

        return new self($values);
    }
}
