<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\{
    Specification\PrimitiveType,
    Specification\ClassType,
    Specification\VariableType,
    Specification\MixedType,
    Specification\NullableType,
    Specification\UnionType
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
        if ($type === '?null') {
            throw new \ParseError('\'null\' type is already nullable');
        }

        if ($type === '?mixed') {
            throw new \ParseError('\'mixed\' type already accepts \'null\' values');
        }

        $type = Str::of($type);

        if ($type->contains('|') && $type->contains('?')) {
            throw new \ParseError('Nullable expression is not allowed in a union type');
        }

        if ($type->contains('|')) {
            return new UnionType(
                ...$type->split('|')->reduce(
                    [],
                    static function(array $types, Str $type): array {
                        $types[] = self::of((string) $type);

                        return $types;
                    }
                )
            );
        }

        if ($type->startsWith('?')) {
            return new NullableType(
                self::ofPrimitive((string) $type->drop(1))
            );
        }

        return self::ofPrimitive((string) $type);
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

    private static function ofPrimitive(string $type): SpecificationInterface
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
}
