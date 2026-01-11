<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

/**
 * @psalm-immutable
 * @template T
 */
final class Lookup
{
    /**
     * @param Implementation<T> $implementation
     */
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    /**
     * @psalm-pure
     * @template A
     * @internal
     *
     * @param Implementation<A> $implementation
     *
     * @return self<A>
     */
    public static function of(Implementation $implementation): self
    {
        return new self($implementation);
    }

    /**
     * @return Lookup\First<T>
     */
    public function first(): Lookup\First
    {
        return Lookup\First::of($this->implementation);
    }

    /**
     * @return Lookup\Last<T>
     */
    public function last(): Lookup\Last
    {
        return Lookup\Last::of($this->implementation);
    }
}
