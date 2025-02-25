<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence\Iterator;

/**
 * @internal
 * @template-covariant T
 * @implements \Iterator<T>
 */
final class Primitive implements \Iterator
{
    /**
     * @psalm-mutation-free
     *
     * @param \ArrayIterator<int<0, max>, T> $inner
     */
    private function __construct(
        private \ArrayIterator $inner,
    ) {
    }

    /**
     * @internal
     * @template A
     * @psalm-pure
     *
     * @param list<A> $values
     *
     * @return self<A>
     */
    public static function of(array $values): self
    {
        /** @psalm-suppress ImpureMethodCall */
        return new self(new \ArrayIterator($values));
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
        $this->inner->rewind();
    }
}
