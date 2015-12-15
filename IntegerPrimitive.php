<?php

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\TypeException;

class IntegerPrimitive implements PrimitiveInterface
{
    private $value;

    public function __construct($value)
    {
        if (!is_int($value)) {
            throw new TypeException('Value must be an integer');
        }

        $this->value = (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive()
    {
        return $this->value;
    }
}
