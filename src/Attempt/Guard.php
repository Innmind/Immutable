<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Attempt;

/**
 * @internal
 * @psalm-immutable
 */
final class Guard
{
    public function __construct(
        private \Throwable $e,
    ) {
    }

    public function unwrap(): \Throwable
    {
        return $this->e;
    }
}
