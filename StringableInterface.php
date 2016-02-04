<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

interface StringableInterface
{
    /**
     * Return the string representation of the object
     *
     * @return string
     */
    public function __toString(): string;
}
