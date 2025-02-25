<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence\Iterator;

use Innmind\Immutable\RegisterCleanup;

/**
 * @internal
 * @template-covariant T
 * @implements \Iterator<T>
 */
final class Lazy implements \Iterator
{
    /**
     * @psalm-mutation-free
     *
     * @param \Generator<int<0, max>, T> $inner
     */
    private function __construct(
        private \Generator $inner,
        private RegisterCleanup $register,
    ) {
    }

    /**
     * @internal
     * @template A
     * @psalm-pure
     *
     * @param \Closure(RegisterCleanup): \Generator<int<0, max>, A> $generator
     *
     * @return self<A>
     */
    public static function of(
        \Closure $generator,
        RegisterCleanup $register,
    ): self {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($generator($register), $register);
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
        $this->register->cleanup();
    }
}
