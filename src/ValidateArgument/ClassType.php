<?php
declare(strict_types = 1);

namespace Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\{
    ValidateArgument,
    Type,
};

final class ClassType implements ValidateArgument
{
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($value, int $position): void
    {
        if (!$value instanceof $this->class) {
            $given = Type::determine($value);

            throw new \TypeError("Argument $position must be of type {$this->class}, $given given");
        }
    }
}
