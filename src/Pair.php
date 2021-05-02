<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Used to identify a key/value pair
 *
 * @template T
 * @template S
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
