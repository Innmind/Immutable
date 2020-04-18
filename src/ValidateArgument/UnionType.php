<?php
declare(strict_types = 1);

namespace Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\{
    ValidateArgument,
    Type,
};

/**
 * @psalm-immutable
 */
final class UnionType implements ValidateArgument
{
    private string $type;
    /** @var list<ValidateArgument> */
    private array $types;

    public function __construct(
        string $type,
        ValidateArgument $first,
        ValidateArgument $second,
        ValidateArgument ...$rest
    ) {
        $this->type = $type;
        $this->types = [$first, $second, ...$rest];
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke($value, int $position): void
    {
        foreach ($this->types as $validate) {
            try {
                $validate($value, $position);

                return;
            } catch (\TypeError $e) {
                // try next type
            }
        }

        $given = Type::determine($value);

        throw new \TypeError("Argument $position must be of type {$this->type}, $given given");
    }
}
