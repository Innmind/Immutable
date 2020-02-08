<?php
declare(strict_types = 1);

namespace Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\{
    ValidateArgument,
    Type,
};

final class ResourceType implements ValidateArgument
{
    /**
     * {@inheritdoc}
     */
    public function __invoke($value, int $position): void
    {
        if (\is_resource($value)) {
            return;
        }

        $given = Type::determine($value);

        if ($given === 'resource (closed)') {
            return;
        }

        throw new \TypeError("Argument $position must be of type resource, $given given");
    }
}
