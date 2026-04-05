<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence\Union;

/**
 * @psalm-immutable
 * @template L
 * @template R
 */
final class Both
{
    /**
     * @param L $left
     * @param R $right
     */
    private function __construct(
        private mixed $left,
        private mixed $right,
    ) {
    }

    /**
     * @internal
     * @psalm-pure
     * @template A
     * @template B
     *
     * @param A $left
     * @param B $right
     *
     * @return self<A, B>
     */
    public static function of(mixed $left, mixed $right): self
    {
        return new self($left, $right);
    }

    /**
     * @return L
     */
    #[\NoDiscard]
    public function left(): mixed
    {
        return $this->left;
    }

    /**
     * @return R
     */
    #[\NoDiscard]
    public function right(): mixed
    {
        return $this->right;
    }
}
