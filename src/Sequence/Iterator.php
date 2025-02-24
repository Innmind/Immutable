<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Accumulate,
    RegisterCleanup,
};

/**
 * @internal
 * @template-covariant T
 * @implements \Iterator<T>
 */
final class Iterator implements \Iterator
{
    /**
     * @psalm-mutation-free
     *
     * @param \ArrayIterator<int<0, max>, T>|Accumulate<T>|\Generator<int<0, max>, T> $inner
     */
    private function __construct(
        private \ArrayIterator|Accumulate|\Generator $inner,
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
    public static function primitive(array $values): self
    {
        /** @psalm-suppress ImpureMethodCall */
        return new self(new \ArrayIterator($values));
    }

    /**
     * @internal
     * @template A
     * @psalm-pure
     *
     * @param Accumulate<A> $values
     *
     * @return self<A>
     */
    public static function defer(Accumulate $values): self
    {
        return new self($values);
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
    public static function lazy(
        \Closure $generator,
        RegisterCleanup $register,
    ): self {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($generator($register));
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
}
