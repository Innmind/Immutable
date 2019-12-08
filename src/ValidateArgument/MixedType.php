<?php
declare(strict_types = 1);

namespace Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\ValidateArgument;

final class MixedType implements ValidateArgument
{
    /**
     * {@inheritdoc}
     */
    public function __invoke($value, int $position): void
    {
        //pass
    }
}
