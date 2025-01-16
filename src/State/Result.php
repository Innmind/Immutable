<?php
declare(strict_types = 1);

namespace Innmind\Immutable\State;

/**
 * @psalm-immutable
 * @template S
 * @template T
 * @deprecated
 */
final class Result
{
    /** @var S */
    private $state;
    /** @var T */
    private $value;

    /**
     * @param S $state
     * @param T $value
     */
    private function __construct($state, $value)
    {
        $this->state = $state;
        $this->value = $value;
    }

    /**
     * @psalm-pure
     * @template A
     * @template B
     *
     * @param A $state
     * @param B $value
     *
     * @return self<A, B>
     */
    public static function of($state, $value): self
    {
        return new self($state, $value);
    }

    /**
     * @return S
     */
    public function state()
    {
        return $this->state;
    }

    /**
     * @return T
     */
    public function value()
    {
        return $this->value;
    }
}
