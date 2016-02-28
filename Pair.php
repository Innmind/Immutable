<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Used to identify a key/value pair
 */
class Pair
{
    private $key;
    private $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function key()
    {
        return $this->key;
    }

    public function value()
    {
        return $this->value;
    }
}
