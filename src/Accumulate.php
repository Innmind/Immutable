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
 * @psalm-immutable Not really immutable but to simplify declaring immutability of other structures
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
    public function current(): mixed
    {
        /** @psalm-suppress UnusedMethodCall */
        $this->pop();

        return \current($this->values);
    }

    /**
     * @return T
     */
    public function key(): mixed
    {
        /** @psalm-suppress UnusedMethodCall */
        $this->pop();

        return \current($this->keys);
    }

    public function next(): void
    {
        \next($this->keys);
        \next($this->values);

        if ($this->reachedCacheEnd()) {
            /** @psalm-suppress ImpureMethodCall */
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
        /** @psalm-suppress ImpureMethodCall */
        $valid = !$this->reachedCacheEnd() || $this->generator->valid();

        if (!$valid) {
            // once the "true" end has been reached we automatically rewind this
            // iterator so it is always in a clean state
            /** @psalm-suppress UnusedMethodCall */
            $this->rewind();
        }

        return $valid;
    }

    private function reachedCacheEnd(): bool
    {
        return \key($this->values) === null;
    }

    private function pop(): void
    {
        if ($this->reachedCacheEnd()) {
            /**
             * @psalm-suppress InaccessibleProperty
             * @psalm-suppress ImpureMethodCall
             */
            $this->keys[] = $this->generator->key();
            /**
             * @psalm-suppress InaccessibleProperty
             * @psalm-suppress ImpureMethodCall
             */
            $this->values[] = $this->generator->current();
        }
    }
}
