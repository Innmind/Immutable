<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\State\Result;

/**
 * @psalm-immutable
 * @template-covariant S
 * @template-covariant T
 * @deprecated
 * @psalm-suppress DeprecatedClass
 */
final class State
{
    /** @var callable(S): Result<S, T> */
    private $run;

    /**
     * @param callable(S): Result<S, T> $run
     */
    private function __construct(callable $run)
    {
        $this->run = $run;
    }

    /**
     * @psalm-pure
     * @template A
     * @template B
     *
     * @param callable(A): Result<A, B> $run
     *
     * @return self<A, B>
     */
    #[\NoDiscard]
    public static function of(callable $run): self
    {
        return new self($run);
    }

    /**
     * @template U
     *
     * @param callable(T): U $map
     *
     * @return self<S, U>
     */
    #[\NoDiscard]
    public function map(callable $map): self
    {
        $run = $this->run;

        return new self(static function(mixed $state) use ($run, $map) {
            /** @var S $state */
            $result = $run($state);

            return Result::of($result->state(), $map($result->value()));
        });
    }

    /**
     * @template A
     *
     * @param callable(T): self<S, A> $map
     *
     * @return self<S, A>
     */
    #[\NoDiscard]
    public function flatMap(callable $map): self
    {
        $run = $this->run;

        return new self(static function(mixed $state) use ($run, $map) {
            /** @var S $state */
            $result = $run($state);

            return $map($result->value())->run($result->state());
        });
    }

    /**
     * @param S $state
     *
     * @return Result<S, T>
     */
    #[\NoDiscard]
    public function run($state): Result
    {
        /** @psalm-suppress ImpureFunctionCall */
        return ($this->run)($state);
    }
}
