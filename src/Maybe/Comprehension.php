<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Maybe;

use Innmind\Immutable\Maybe;

/**
 * @psalm-immutable
 */
final class Comprehension
{
    private Maybe $first;
    /** @var list<Maybe> */
    private array $rest;

    /**
     * @no-named-arguments
     */
    private function __construct(Maybe $first, Maybe ...$rest)
    {
        $this->first = $first;
        $this->rest = $rest;
    }

    /**
     * @internal
     * @psalm-pure
     * @no-named-arguments
     */
    public static function of(Maybe $first, Maybe ...$rest): self
    {
        return new self($first, ...$rest);
    }

    /**
     * @template T
     *
     * @param callable(...mixed): T $map
     *
     * @return Maybe<T>
     */
    #[\NoDiscard]
    public function map(callable $map): Maybe
    {
        return $this->collapse()->map(static fn(array $args) => $map(...$args));
    }

    /**
     * @template T
     *
     * @param callable(...mixed): Maybe<T> $map
     *
     * @return Maybe<T>
     */
    #[\NoDiscard]
    public function flatMap(callable $map): Maybe
    {
        return $this->collapse()->flatMap(static fn(array $args) => $map(...$args));
    }

    /**
     * @return Maybe<list<mixed>>
     */
    private function collapse(): Maybe
    {
        /**
         * @psalm-suppress MixedArgumentTypeCoercion
         * @var Maybe<list<mixed>>
         */
        return \array_reduce(
            $this->rest,
            static fn(Maybe $carry, Maybe $maybe): Maybe => $carry->flatMap(
                static fn(array $args) => $maybe->map(
                    static fn($value) => \array_merge($args, [$value]),
                ),
            ),
            $this->first->map(static fn($value) => [$value]),
        );
    }
}
