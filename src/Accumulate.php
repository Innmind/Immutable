<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Simple iterator to cache the results of a generator so it can be iterated
 * over multiple times
 *
 * @template T
 * @template S
 * @internal Do not use this in your code
 */
final class Accumulate implements \Iterator
{
    /** @var \Generator<T, S> */
    private \Generator $generator;
    /** @var list<T> */
    private array $keys = [];
    /** @var list<S> */
    private array $values = [];

    /**
     * @param \Generator<T, S> $generator
     */
    public function __construct(\Generator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @return S
     */
    public function current()
    {
        $this->pop();

        return \current($this->values);
    }

    /**
     * @return T
     */
    public function key()
    {
        $this->pop();

        return \current($this->keys);
    }

    public function next(): void
    {
        \next($this->keys);
        \next($this->values);

        if ($this->reachedCacheEnd()) {
            $this->generator->next();
        }
    }

    public function rewind(): void
    {
        \reset($this->keys);
        \reset($this->values);
    }

    public function valid(): bool
    {
        return !$this->reachedCacheEnd() || $this->generator->valid();
    }

    private function reachedCacheEnd(): bool
    {
        return \key($this->values) === null;
    }

    private function pop(): void
    {
        if ($this->reachedCacheEnd()) {
            $this->keys[] = $this->generator->key();
            $this->values[] = $this->generator->current();
        }
    }
}
