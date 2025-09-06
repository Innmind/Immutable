<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Attempt\{
    Implementation,
    Error,
    Result,
    Defer,
};

/**
 * @template-covariant T
 * @psalm-immutable
 */
final class Attempt
{
    /**
     * @param Implementation<T> $implementation
     */
    private function __construct(
        private Implementation $implementation,
    ) {
    }

    /**
     * @template U
     * @psalm-pure
     *
     * @return self<U>
     */
    #[\NoDiscard]
    public static function error(\Throwable $error): self
    {
        return new self(new Error($error));
    }

    /**
     * @template U
     * @psalm-pure
     *
     * @param U $value
     *
     * @return self<U>
     */
    #[\NoDiscard]
    public static function result(mixed $value): self
    {
        return new self(new Result($value));
    }

    /**
     * This method is to be used for IO operations
     *
     * @template U
     * @psalm-pure
     *
     * @param callable(): self<U> $deferred
     *
     * @return self<U>
     */
    #[\NoDiscard]
    public static function defer(callable $deferred): self
    {
        return new self(new Defer($deferred));
    }

    /**
     * @template U
     * @psalm-pure
     *
     * @param callable(): U $try
     *
     * @return self<U>
     */
    #[\NoDiscard]
    public static function of(callable $try): self
    {
        try {
            /** @psalm-suppress ImpureFunctionCall */
            return self::result($try());
        } catch (\Throwable $e) {
            return self::error($e);
        }
    }

    /**
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return self<U>
     */
    #[\NoDiscard]
    public function map(callable $map): self
    {
        return new self($this->implementation->map($map));
    }

    /**
     * @template U
     *
     * @param callable(T): self<U> $map
     *
     * @return self<U>
     */
    #[\NoDiscard]
    public function flatMap(callable $map): self
    {
        return new self($this->implementation->flatMap(
            $map,
            static fn(self $self) => $self->implementation,
        ));
    }

    /**
     * @template U
     *
     * @param callable(T): U $result
     * @param callable(\Throwable): U $error
     *
     * @return U
     */
    #[\NoDiscard]
    public function match(callable $result, callable $error)
    {
        return $this->implementation->match($result, $error);
    }

    /**
     * Be aware that this call is not safe as it may throw an exception.
     *
     * @throws \Throwable
     *
     * @return T
     */
    #[\NoDiscard]
    public function unwrap(): mixed
    {
        /** @var T */
        return $this->match(
            static fn(mixed $value): mixed => $value,
            static fn($e) => throw $e,
        );
    }

    /**
     * @param callable(\Throwable): \Throwable $map
     *
     * @return self<T>
     */
    #[\NoDiscard]
    public function mapError(callable $map): self
    {
        return new self($this->implementation->mapError($map));
    }

    /**
     * @template U
     *
     * @param callable(\Throwable): self<U> $recover
     *
     * @return self<T|U>
     */
    #[\NoDiscard]
    public function recover(callable $recover): self
    {
        return new self($this->implementation->recover(
            $recover,
            static fn(self $self) => $self->implementation,
        ));
    }

    /**
     * @return Maybe<T>
     */
    #[\NoDiscard]
    public function maybe(): Maybe
    {
        return $this->implementation->maybe();
    }

    /**
     * @return Either<\Throwable, T>
     */
    #[\NoDiscard]
    public function either(): Either
    {
        return $this->implementation->either();
    }

    /**
     * Force loading the value in memory (only useful for a deferred Attempt)
     *
     * @return self<T>
     */
    #[\NoDiscard]
    public function memoize(): self
    {
        return new self($this->implementation->memoize(
            static fn(self $self) => $self->implementation,
        ));
    }

    /**
     * @template V
     *
     * @param callable(T): self<V> $result
     * @param callable(\Throwable): self<V> $error
     *
     * @return self<V>
     */
    #[\NoDiscard]
    public function eitherWay(callable $result, callable $error): self
    {
        return new self($this->implementation->eitherWay(
            $result,
            $error,
            static fn(self $self) => $self->implementation,
        ));
    }
}
