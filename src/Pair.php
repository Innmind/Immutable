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
    /** @var T */
    private $key;
    /** @var S */
    private $value;

    /**
     * @internal You should never have to manually create this object
     * @param T $key
     * @param S $value
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @return T
     */
    #[\NoDiscard]
    public function key()
    {
        return $this->key;
    }

    /**
     * @return S
     */
    #[\NoDiscard]
    public function value()
    {
        return $this->value;
    }
}
