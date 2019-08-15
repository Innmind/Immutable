<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Used to identify a key/value pair
 *
 * @template T
 * @template S
 */
class Pair
{
    private $key;
    private $value;

    /**
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
