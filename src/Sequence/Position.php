<?php

declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 */
enum Position
{
    case first;
    case middle;
    case last;
    case only;

    /**
     * @psalm-pure
     * @template T
     *
     * @param Sequence<T> $sequence
     *
     * @return Sequence<Positionned<T>>
     */
    public static function tag(Sequence $sequence): Sequence
    {
        return $sequence
            ->map(Positionned::of(...))
            ->aggregate(static fn(Positionned $a, $b) => $a->and($b));
    }

    public function first(): bool
    {
        return match ($this) {
            self::first => true,
            self::middle => false,
            self::last => false,
            self::only => true,
        };
    }

    public function last(): bool
    {
        return match ($this) {
            self::first => false,
            self::middle => false,
            self::last => true,
            self::only => true,
        };
    }
}
