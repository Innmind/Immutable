<?php

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\TypeException;

class BooleanPrimitive implements PrimitiveInterface
{
    private $value;

    public function __construct($value)
    {
        if (!is_bool($value)) {
            throw new TypeException('Value must be a boolean');
        }

        $this->value = (bool) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive()
    {
        return $this->value;
    }
}
