<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    Specification\PrimitiveType,
    Specification\ClassType,
    Specification\VariableType
};

trait Type
{
    /**
     * Build the approprivate specification for the given type
     *
     * @param string $type
     *
     * @return SpecificationInterface
     */
    private function getSpecificationFor(string $type): SpecificationInterface
    {
        if (function_exists('is_'.$type)) {
            return new PrimitiveType($type);
        }

        if ($type === 'variable') {
            return new VariableType;
        }

        return new ClassType($type);
    }
}
