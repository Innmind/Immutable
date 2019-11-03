<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Specification;

use Innmind\Immutable\SpecificationInterface;

final class NullableType implements SpecificationInterface
{
    private $type;

    public function __construct(SpecificationInterface $type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value): void
    {
        if (\is_null($value)) {
            return;
        }

        $this->type->validate($value);
    }
}
