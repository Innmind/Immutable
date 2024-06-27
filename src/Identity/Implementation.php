<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Identity;

use Innmind\Immutable\Identity;

/**
 * @psalm-immutable
 * @template T
 */
interface Implementation
{
    /**
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return self<U>
     */
    public function map(callable $map): self;

    /**
     * @template U
     *
     * @param callable(T): Identity<U> $map
     *
     * @return Identity<U>
     */
    public function flatMap(callable $map): Identity;

    /**
     * @return T
     */
    public function unwrap(): mixed;
}
