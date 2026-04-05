<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence\Union;

/**
 * @psalm-immutable
 * @template T
 */
final class Left
{
    /**
     * @param T $value
     */
    private function __construct(
        private mixed $value,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     * @template A
     *
     * @param A $value
     *
     * @return self<A>
     */
    public static function of(mixed $value): self
    {
        return new self($value);
    }

    /**
     * @return T
     */
    #[\NoDiscard]
    public function unwrap(): mixed
    {
        return $this->value;
    }

    /**
     * @return T
     */
    #[\NoDiscard]
    public function left(): mixed
    {
        return $this->unwrap();
    }
}
