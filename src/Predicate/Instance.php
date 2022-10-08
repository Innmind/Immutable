<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Predicate;

use Innmind\Immutable\Predicate;

/**
 * @psalm-immutable
 * @template A of object
 * @implements Predicate<A>
 */
final class Instance implements Predicate
{
    /** @var class-string<A> */
    private string $class;

    /**
     * @param class-string<A> $class
     */
    private function __construct(string $class)
    {
        $this->class = $class;
    }

    public function __invoke(mixed $value): bool
    {
        return $value instanceof $this->class;
    }

    /**
     * @psalm-pure
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return self<T>
     */
    public static function of(string $class): self
    {
        return new self($class);
    }
}
