<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Simple iterator to cache the results of a generator so it can be iterated
 * over multiple times
 *
 * @template S
 * @implements \Iterator<S>
 * @internal Do not use this in your code
 * @psalm-immutable Not really immutable but to simplify declaring immutability of other structures
 */
final class Accumulate implements \Iterator
{
    /** @var \Generator<S> */
    private \Generator $generator;
    /** @var list<S> */
    private array $values = [];
    /** @var list<int<0, max>> */
    private array $cursors = [];
    private bool $started = false;

    /**
     * @param \Generator<S> $generator
     */
    public function __construct(\Generator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @return S
     */
    #[\Override]
    public function current(): mixed
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->started = true;
        /** @psalm-suppress UnusedMethodCall */
        $this->pop();

        /** @var S */
        return \current($this->values);
    }

    /**
     * @return int<0, max>|null
     */
    #[\Override]
    public function key(): ?int
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->started = true;
        /** @psalm-suppress UnusedMethodCall */
        $this->pop();

        return \key($this->values);
    }

    #[\Override]
    public function next(): void
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->started = true;
        /** @psalm-suppress InaccessibleProperty */
        \next($this->values);

        if ($this->reachedCacheEnd()) {
            /** @psalm-suppress ImpureMethodCall */
            $this->generator->next();
        }
    }

    #[\Override]
    public function rewind(): void
    {
        if ($this->started && !\is_null($key = $this->key())) {
            /** @psalm-suppress InaccessibleProperty */
            $this->cursors[] = $key;
        }

        /** @psalm-suppress InaccessibleProperty */
        $this->started = true;
        /** @psalm-suppress InaccessibleProperty */
        \reset($this->values);
    }

    #[\Override]
    public function valid(): bool
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->started = true;
        /** @psalm-suppress ImpureMethodCall */
        $valid = !$this->reachedCacheEnd() || $this->generator->valid();

        if (!$valid) {
            // once the "true" end has been reached we automatically rewind this
            // iterator so it is always in a clean state
            $this->cleanup();
        }

        return $valid;
    }

    public function started(): bool
    {
        return $this->started;
    }

    public function cleanup(): void
    {
        /** @psalm-suppress InaccessibleProperty */
        $previousCursor = \array_pop($this->cursors);

        if (\is_null($previousCursor)) {
            return;
        }

        // Re-position the cursor to the previous position before entering a new
        // loop. It only iterate over the cached values because the previous
        // cursor must be in the cache.
        /** @psalm-suppress InaccessibleProperty */
        \reset($this->values);

        while (\is_int(\key($this->values)) && \key($this->values) !== $previousCursor) {
            /** @psalm-suppress InaccessibleProperty */
            \next($this->values);
        }
    }

    private function reachedCacheEnd(): bool
    {
        return \key($this->values) === null;
    }

    private function pop(): void
    {
        /** @psalm-suppress ImpureMethodCall */
        if ($this->reachedCacheEnd() && $this->generator->valid()) {
            /**
             * @psalm-suppress InaccessibleProperty
             * @psalm-suppress ImpureMethodCall
             */
            $this->values[] = $this->generator->current();
        }
    }
}
