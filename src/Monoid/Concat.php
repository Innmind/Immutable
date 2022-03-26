<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Monoid;

use Innmind\Immutable\{
    Monoid,
    Str,
};

/**
 * @psalm-immutable
 * @implements Monoid<Str>
 */
final class Concat implements Monoid
{
    public function identity(): Str
    {
        return Str::of('');
    }

    public function combine(mixed $a, mixed $b): Str
    {
        return $a->append($b->toString());
    }
}
