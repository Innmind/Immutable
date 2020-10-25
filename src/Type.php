<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\ValidateArgument\{
    PrimitiveType,
    ClassType,
    VariableType,
    MixedType,
    NullableType,
    UnionType,
    ResourceType,
};

final class Type
{
    /**
     * Build the appropriate specification for the given type
     */
    public static function of(string $type): ValidateArgument
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
            /** @var list<ValidateArgument> */
            $types = $type->split('|')->reduce(
                [],
                static function(array $types, Str $type): array {
                    $types[] = self::of($type->toString());

                    return $types;
                },
            );

            return new UnionType($type->toString(), ...$types);
        }

        if ($type->startsWith('?')) {
            return new NullableType(
                self::ofPrimitive($type->drop(1)->toString()),
            );
        }

        return self::ofPrimitive($type->toString());
    }

    /**
     * Return the type of the given value
     *
     * @param mixed $value
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

    private static function ofPrimitive(string $type): ValidateArgument
    {
        if ($type === 'resource') {
            return new ResourceType;
        }

        if (\function_exists('is_'.$type)) {
            return new PrimitiveType($type);
        }

        if ($type === 'variable') {
            return new UnionType(
                'scalar|array',
                new PrimitiveType('scalar'),
                new PrimitiveType('array'),
            );
        }

        if ($type === 'mixed') {
            return new MixedType;
        }

        return new ClassType($type);
    }
}
