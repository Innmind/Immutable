<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence\Sink;

/**
 * @template T
 * @psalm-immutable
 */
final class Continuation
{
    /**
     * @param T $carry
     */
    private function __construct(
        private mixed $carry,
        private bool $continue,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     * @template A
     *
     * @return self<A>
     */
    public static function of(mixed $carry): self
    {
        return new self($carry, true);
    }

    /**
     * @param T $carry
     *
     * @return self<T>
     */
    public function continue(mixed $carry): self
    {
        return new self($carry, true);
    }

    /**
     * @param T $carry
     *
     * @return self<T>
     */
    public function stop(mixed $carry): self
    {
        return new self($carry, false);
    }

    /**
     * @internal
     */
    public function shouldContinue(): bool
    {
        return $this->continue;
    }

    /**
     * @internal
     *
     * @return T
     */
    public function unwrap(): mixed
    {
        return $this->carry;
    }
}
