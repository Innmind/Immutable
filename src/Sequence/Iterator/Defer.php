<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence\Iterator;

use Innmind\Immutable\Accumulate;

/**
 * @internal
 * @template-covariant T
 * @implements \Iterator<T>
 */
final class Defer implements \Iterator
{
    /**
     * @psalm-mutation-free
     *
     * @param Accumulate<T>|\Generator<int<0, max>, T> $inner
     */
    private function __construct(
        private Accumulate|\Generator $inner,
    ) {
    }

    /**
     * @internal
     * @template A
     * @psalm-pure
     *
     * @param Accumulate<A>|\Generator<int<0, max>, A> $values
     *
     * @return self<A>
     */
    public static function of(Accumulate|\Generator $values): self
    {
        return new self($values);
    }

    /**
     * @return T
     */
    #[\Override]
    public function current(): mixed
    {
        return $this->inner->current();
    }

    /**
     * @return ?int<0, max>
     */
    #[\Override]
    public function key(): ?int
    {
        return $this->inner->key();
    }

    #[\Override]
    public function next(): void
    {
        $this->inner->next();
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->inner->valid();
    }

    #[\Override]
    public function rewind(): void
    {
        $this->inner->rewind();
    }

    public function cleanup(): void
    {
        if ($this->inner instanceof Accumulate) {
            $this->inner->cleanup();
        }

        // If we deal with a generator then it means the intermediary Set is not
        // directly used. So there's a parent Accumulate consuming this
        // generator that will correctly cleanup its own cursor.
    }
}
