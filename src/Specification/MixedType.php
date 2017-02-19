<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Specification;

use Innmind\Immutable\SpecificationInterface;

final class MixedType implements SpecificationInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate($value)
    {
        //pass
    }
}
