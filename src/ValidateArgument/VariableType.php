<?php
declare(strict_types = 1);

namespace Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\{
    ValidateArgument,
    Type,
};

final class VariableType implements ValidateArgument
{
    /**
     * {@inheritdoc}
     */
    public function __invoke($value, int $position): void
    {
        if (!\is_scalar($value) && !\is_array($value)) {
            $given = Type::determine($value);

            throw new \TypeError("Argument $position must be of type variable, $given given");
        }
    }
}
