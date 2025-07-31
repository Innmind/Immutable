<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @psalm-immutable
 * @template T
 */
interface Monoid
{
    /**
     * @return T
     */
    #[\NoDiscard]
    public function identity(): mixed;

    /**
     * @param T $a
     * @param T $b
     *
     * @return T
     */
    #[\NoDiscard]
    public function combine(mixed $a, mixed $b): mixed;
}
