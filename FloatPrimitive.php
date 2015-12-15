<?php

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\TypeException;

class FloatPrimitive implements PrimitiveInterface
{
    private $value;

    public function __construct($value)
    {
        if (!is_float($value)) {
            throw new TypeException('Value must be a float');
        }

        $this->value = (float) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive()
    {
        return $this->value;
    }
}
