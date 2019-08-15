<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Specification;

use Innmind\Immutable\{
    SpecificationInterface,
    Exception\InvalidArgumentException
};

final class VariableType implements SpecificationInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate($value): void
    {
        if (!\is_scalar($value) && !\is_array($value)) {
            throw new InvalidArgumentException;
        }
    }
}
