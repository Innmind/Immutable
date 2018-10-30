<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    Specification\PrimitiveType,
    Specification\ClassType,
    Specification\VariableType,
    Specification\MixedType
};

final class Type
{
    /**
     * Build the appropriate specification for the given type
     *
     * @param string $type
     *
     * @return SpecificationInterface
     */
    public static function of(string $type): SpecificationInterface
    {
        if (\function_exists('is_'.$type)) {
            return new PrimitiveType($type);
        }

        if ($type === 'variable') {
            return new VariableType;
        }

        if ($type === 'mixed') {
            return new MixedType;
        }

        return new ClassType($type);
    }

    /**
     * Return the type of the given value
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function determine($value): string
    {
        $type = \gettype($value);

        switch ($type) {
            case 'object':
                return \get_class($value);

            case 'integer':
                return 'int';

            case 'boolean':
                return 'bool';

            case 'NULL':
                return 'null';

            case 'double':
                return 'float';

            default:
                return $type;
        }
    }
}
