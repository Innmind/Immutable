<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Specification;

use Innmind\Immutable\{
    SpecificationInterface,
    Exception\InvalidArgumentException
};

class PrimitiveType implements SpecificationInterface
{
    private $function;

    public function __construct(string $type)
    {
        $this->function = 'is_'.$type;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value): void
    {
        if (call_user_func($this->function, $value) === false) {
            throw new InvalidArgumentException;
        }
    }
}
