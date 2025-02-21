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
    public function key(): ?int
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->started = true;
        /** @psalm-suppress UnusedMethodCall */
        $this->pop();

        return \key($this->values);
    }

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

    public function rewind(): void
    {
        if (\is_int($cursor = \key($this->values))) {
            /** @psalm-suppress InaccessibleProperty */
            $this->cursors[] = $cursor;
        }

        /** @psalm-suppress InaccessibleProperty */
        $this->started = true;
        /** @psalm-suppress InaccessibleProperty */
        \reset($this->values);
    }

    public function valid(): bool
    {
        /** @psalm-suppress InaccessibleProperty */
        $this->started = true;
        /** @psalm-suppress ImpureMethodCall */
        $valid = !$this->reachedCacheEnd() || $this->generator->valid();

        if (!$valid) {
            // once the "true" end has been reached we automatically rewind to
            // the previous cursor position to allow correct iteration when
            // nesting loops on the same iterator
            /** @psalm-suppress InaccessibleProperty */
            $previousCursor = \array_pop($this->cursors);
            /** @psalm-suppress InaccessibleProperty */
            \reset($this->values);

            if (\is_int($previousCursor)) {
                while (\is_int(\key($this->values)) && \key($this->values) !== $previousCursor) {
                    /** @psalm-suppress InaccessibleProperty */
                    \next($this->values);
                }
            }
        }

        return $valid;
    }

    public function started(): bool
    {
        return $this->started;
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
            $this->values[] = $this->generator->current();
        }
    }
}
