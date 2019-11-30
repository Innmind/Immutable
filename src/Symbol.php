<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\InvalidArgumentException;

/**
 * Think of it as a unique identifier
 *
 * @template T
 */
class Symbol
{
    private $value;

    /**
     * @param T $value
     */
    public function __construct($value)
    {
        if (!\is_int($value) && !\is_string($value)) {
            throw new InvalidArgumentException(
                'A Symbol can be composed only of an int or a string'
            );
        }

        $this->value = $value;
    }

    /**
     * @return T
     */
    public function value()
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
