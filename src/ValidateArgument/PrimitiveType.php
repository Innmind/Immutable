<?php
declare(strict_types = 1);

namespace Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\{
    ValidateArgument,
    Type,
};

final class PrimitiveType implements ValidateArgument
{
    /** @var callable */
    private $function;
    private string $type;

    public function __construct(string $type)
    {
        /** @var callable */
        $this->function = 'is_'.$type;
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($value, int $position): void
    {
        if (($this->function)($value) === false) {
            $given = Type::determine($value);

            throw new \TypeError("Argument $position must be of type {$this->type}, $given given");
        }
    }
}
