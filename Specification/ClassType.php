<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Specification;

use Innmind\Immutable\{
    SpecificationInterface,
    Exception\InvalidArgumentException
};

class ClassType implements SpecificationInterface
{
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value)
    {
        if (!$value instanceof $this->class) {
            throw new InvalidArgumentException;
        }
    }
}
