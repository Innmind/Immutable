<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    Specification\PrimitiveType,
    Specification\ClassType
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
    private function getSpecFor(string $type): SpecificationInterface
    {
        if (function_exists('is_' . $type)) {
            return new PrimitiveType($type);
        }

        return new ClassType($type);
    }
}
