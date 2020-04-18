<?php
declare(strict_types = 1);

namespace Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\ValidateArgument;

/**
 * @psalm-immutable
 */
final class NullableType implements ValidateArgument
{
    private ValidateArgument $validate;

    public function __construct(ValidateArgument $validate)
    {
        $this->validate = $validate;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($value, int $position): void
    {
        if (\is_null($value)) {
            return;
        }

        ($this->validate)($value, $position);
    }
}
