<?php

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\TypeException;

class StringPrimitive implements PrimitiveInterface, StringableInterface
{
    private $value;

    public function __construct($value)
    {
        if (!is_string($value)) {
            throw new TypeException('Value must be a string');
        }

        $this->value = (string) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->value;
    }
}
