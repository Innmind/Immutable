<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Accumulate,
    RegisterCleanup,
    Sequence\Iterator\Lazy,
    Sequence\Iterator\Defer,
    Sequence\Iterator\Primitive,
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
     * @param Lazy<T>|Defer<T>|Primitive<T> $inner
     */
    private function __construct(
        private Lazy|Defer|Primitive $inner,
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
        return new self(Primitive::of($values));
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
    public static function defer(Accumulate|\Generator $values): self
    {
        return new self(Defer::of($values));
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
        return new self(Lazy::of($generator, $register));
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
        $this->inner->cleanup();
    }
}
