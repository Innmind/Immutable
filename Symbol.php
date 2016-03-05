<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\InvalidArgumentException;

/**
 * Think of it as a unique identifier
 */
class Symbol implements PrimitiveInterface
{
    private $value;

    public function __construct($value)
    {
        if (!is_int($value) && !is_string($value)) {
            throw new InvalidArgumentException(
                'A Symbol can be composed only of an int or a string'
            );
        }

        $this->value = $value;
    }

    public function toPrimitive()
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
