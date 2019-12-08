<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

interface ValidateArgument
{
    /**
     * Check if the given value is validated by the spec
     *
     * @param mixed $value
     *
     * @throws \TypeError If the validation fails
     */
    public function __invoke($value, int $position): void;
}
