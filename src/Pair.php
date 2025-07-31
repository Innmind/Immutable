<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Used to identify a key/value pair
 *
 * @template T
 * @template S
 * @psalm-immutable
 */
final class Pair
{
    /**
     * @internal You should never have to manually create this object
     * @param T $key
     * @param S $value
     */
    public function __construct(
        private mixed $key,
        private mixed $value,
    ) {
    }

    /**
     * @return T
     */
    public function key()
    {
        return $this->key;
    }

    /**
     * @return S
     */
    public function value()
    {
        return $this->value;
    }
}
