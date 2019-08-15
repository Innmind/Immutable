<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

interface SpecificationInterface
{
    /**
     * Check if the given value is validated by the spec
     *
     * @param mixed $value
     *
     * @throws InvalidArgumentException If the validation fails
     */
    public function validate($value): void;
}
