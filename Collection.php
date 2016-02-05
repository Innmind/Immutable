<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\SortException;
use Innmind\Immutable\Exception\OutOfBoundException;
use Innmind\Immutable\Exception\RuntimeException;
use Innmind\Immutable\Exception\InvalidArgumentException;
use Innmind\Immutable\Exception\LogicException;

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
    public function filter(callable $filter = null): CollectionInterface
    {
        if ($filter === null) {
            $values = array_filter($this->values);
        } else {
            $values = array_filter(
                $this->values,
                $filter,
                ARRAY_FILTER_USE_BOTH
            );
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function intersect(CollectionInterface $collection): CollectionInterface
    {
        return new self(array_intersect(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function chunk(int $size): CollectionInterface
    {
        $chunks = array_chunk($this->values, $size);
        $subs = [];

        foreach ($chunks as $chunk) {
            $subs[] = new self($chunk);
        }

        return new self($subs);
    }

    /**
     * {@inheritdoc}
     */
    public function shift(): CollectionInterface
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
    public function search($needle, bool $strict = true)
    {
        return array_search($needle, $this->values, (bool) $strict);
    }

    /**
     * {@inheritdoc}
     */
    public function uintersect(CollectionInterface $collection, callable $intersecter): CollectionInterface
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
    public function keyIntersect(CollectionInterface $collection): CollectionInterface
    {
        return new self(array_intersect_key(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $mapper): CollectionInterface
    {
        return new self(array_map($mapper, $this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function pad(int $size, $value): CollectionInterface
    {
        return new self(array_pad($this->values, $size, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function pop(): CollectionInterface
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
    public function diff(CollectionInterface $collection): CollectionInterface
    {
        return new self(array_diff($this->values, $collection->toPrimitive()));
    }

    /**
     * {@inheritdoc}
     */
    public function flip(): CollectionInterface
    {
        return new self(array_flip($this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function keys($search = null, bool $strict = true): CollectionInterface
    {
        $args = func_get_args();

        if (count($args) > 0) {
            $keys = array_keys($this->values, $search, (bool) $strict);
        } else {
            $keys = array_keys($this->values);
        }

        return new self($keys);
    }

    /**
     * {@inheritdoc}
     */
    public function push($value): CollectionInterface
    {
        $values = $this->values;
        array_push($values, $value);

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function rand(int $num = 1): CollectionInterface
    {
        if ($num > $this->count()) {
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
    public function merge(CollectionInterface $collection): CollectionInterface
    {
        return new self(array_merge(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function slice(int $offset, int $length = null, bool $preserveKeys = false): CollectionInterface
    {
        return new self(array_slice(
            $this->values,
            $offset,
            $length === null ? null : $length,
            $preserveKeys
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function udiff(CollectionInterface $collection, callable $differ): CollectionInterface
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
    public function column($key, $indexKey = null): CollectionInterface
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
    public function splice(int $offset, int $length = 0, $replacement = []): CollectionInterface
    {
        $values = $this->values;
        array_splice($values, $offset, $length, $replacement);

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function unique(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        return new self(array_unique($this->values, $flags));
    }

    /**
     * {@inheritdoc}
     */
    public function values(): CollectionInterface
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
    public function replace(CollectionInterface $collection): CollectionInterface
    {
        return new self(array_replace(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(bool $preserveKeys = false): CollectionInterface
    {
        return new self(array_reverse($this->values, $preserveKeys));
    }

    /**
     * {@inheritdoc}
     */
    public function unshift($value): CollectionInterface
    {
        $values = $this->values;
        array_unshift($values, $value);

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function keyDiff(CollectionInterface $collection): CollectionInterface
    {
        return new self(array_diff_key(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function ukeyDiff(CollectionInterface $collection, callable $differ): CollectionInterface
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
    public function associativeDiff(CollectionInterface $collection): CollectionInterface
    {
        return new self(array_diff_assoc(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function hasKey($key, bool $strict = true): bool
    {
        if ($strict === true) {
            $bool = array_key_exists($key, $this->values);
        } else {
            $bool = isset($this->values[$key]);
        }

        return $bool;
    }

    /**
     * {@inheritdoc}
     */
    public function countValues(): CollectionInterface
    {
        return new self(array_count_values($this->values));
    }

    /**
     * {@inheritdoc}
     */
    public function ukeyIntersect(CollectionInterface $collection, callable $intersecter): CollectionInterface
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
    public function associativeIntersect(CollectionInterface $collection): CollectionInterface
    {
        return new self(array_intersect_assoc(
            $this->values,
            $collection->toPrimitive()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function sort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        $values = $this->values;
        $bool = sort($values, $flags);

        if ($bool === false) {
            throw new SortException('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function associativeSort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        $values = $this->values;
        $bool = asort($values, $flags);

        if ($bool === false) {
            throw new SortException('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function keySort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        $values = $this->values;
        $bool = ksort($values, $flags);

        if ($bool === false) {
            throw new SortException('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function ukeySort(callable $sorter): CollectionInterface
    {
        $values = $this->values;
        $bool = uksort($values, $sorter);

        if ($bool === false) {
            throw new SortException('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseSort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        $values = $this->values;
        $bool = rsort($values, $flags);

        if ($bool === false) {
            throw new SortException('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function usort(callable $sorter): CollectionInterface
    {
        $values = $this->values;
        $bool = usort($values, $sorter);

        if ($bool === false) {
            throw new SortException('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function associativeReverseSort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        $values = $this->values;
        $bool = arsort($values, $flags);

        if ($bool === false) {
            throw new SortException('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function keyReverseSort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        $values = $this->values;
        $bool = krsort($values, $flags);

        if ($bool === false) {
            throw new SortException('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function uassociativeSort(callable $sorter): CollectionInterface
    {
        $values = $this->values;
        $bool = uasort($values, $sorter);

        if ($bool === false) {
            throw new SortException('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function naturalSort(): CollectionInterface
    {
        $values = $this->values;
        $bool = natsort($values);

        if ($bool === false) {
            throw new SortException('Sort failure');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function first()
    {
        if ($this->count() === 0) {
            throw new OutOfBoundException('There is no first item');
        }

        return array_values($this->values)[0];
    }

    /**
     * {@inheritdoc}
     */
    public function last()
    {
        if ($this->count() === 0) {
            throw new OutOfBoundException('There is no last item');
        }

        $values = array_values($this->values);

        return end($values);
    }

    /**
     * {@inheritdoc}
     */
    public function each(\Closure $callback): CollectionInterface
    {
        foreach ($this->values as $key => $value) {
            $callback($key, $value);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): string
    {
        return implode($separator, $this->values);
    }

    /**
     * {@inheritdoc}
     */
    public function shuffle(): CollectionInterface
    {
        $values = $this->values;
        $result = shuffle($values);

        if ($result === false) {
            throw new RuntimeException('Shuffle operation failed');
        }

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function take(int $size, bool $preserveKeys = false): CollectionInterface
    {
        $took = [];
        $keys = array_keys($this->values);

        while (count($took) < $size) {
            do {
                $random = mt_rand(0, count($keys) - 1);
            } while (!isset($keys[$random]));
            $key = $keys[$random];
            $took[$key] = $this->values[$key];
            unset($keys[$random]);
        }

        if ($preserveKeys === false) {
            $took = array_values($took);
        }

        return new self($took);
    }

    /**
     * {@inheritdoc}
     */
    public function grep(string $pattern, bool $revert = false): CollectionInterface
    {
        return new self(preg_grep(
            $pattern,
            $this->values,
            $revert === false ? 0 : PREG_GREP_INVERT
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value): CollectionInterface
    {
        $values = $this->values;
        $values[$key] = $value;

        return new self($values);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($value): bool
    {
        return in_array($value, $this->values, true);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return $this->offsetGet($key);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->values);
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
        return $this->hasKey($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (!$this->hasKey($offset)) {
            throw new InvalidArgumentException(sprintf(
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
        throw new LogicException('You can\'t modify an immutable collection');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        throw new LogicException('You can\'t modify an immutable collection');
    }
}
