<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @psalm-immutable
 * @template T
 */
interface Predicate
{
    /**
     * @psalm-assert-if-true T $value
     */
    public function __invoke(mixed $value): bool;
}
