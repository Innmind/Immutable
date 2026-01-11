<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @psalm-immutable
 * @template T
 */
final class Predicate
{
    /**
     * @param \Closure(mixed): bool $assert
     */
    private function __construct(
        private \Closure $assert,
    ) {
    }

    /**
     * @psalm-assert-if-true T $value
     */
    #[\NoDiscard]
    public function __invoke(mixed $value): bool
    {
        /** @psalm-suppress ImpureFunctionCall */
        return ($this->assert)($value);
    }

    /**
     * @psalm-pure
     *
     * @param callable(mixed): bool $assert
     */
    public static function of(callable $assert): self
    {
        return new self(\Closure::fromCallable($assert));
    }

    /**
     * @template U
     *
     * @param self<U> $predicate
     *
     * @return self<T&U>
     */
    public function and(self $predicate): self
    {
        $self = $this->assert;
        $other = $predicate->assert;

        /** @var self<T&U> */
        return new self(
            static fn($value) => $self($value) && $other($value),
        );
    }

    /**
     * @template U
     *
     * @param self<U> $predicate
     *
     * @return self<T|U>
     */
    public function or(self $predicate): self
    {
        $self = $this->assert;
        $other = $predicate->assert;

        return new self(
            static fn($value) => $self($value) || $other($value),
        );
    }
}
