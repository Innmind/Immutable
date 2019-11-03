<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Specification;

use Innmind\Immutable\{
    SpecificationInterface,
    Exception\InvalidArgumentException
};

final class UnionType implements SpecificationInterface
{
    private $types;

    public function __construct(
        SpecificationInterface $first,
        SpecificationInterface $second,
        SpecificationInterface ...$rest
    ) {
        \array_unshift($rest, $second);
        \array_unshift($rest, $first);

        $this->types = $rest;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value): void
    {
        foreach ($this->types as $type) {
            try {
                $type->validate($value);

                return;
            } catch (InvalidArgumentException $e) {
                // try next type
            }
        }

        throw new InvalidArgumentException;
    }
}
