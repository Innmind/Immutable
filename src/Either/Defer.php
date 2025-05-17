<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Either;

use Innmind\Immutable\{
    Either,
    Maybe,
    Attempt,
};

/**
 * @template L1
 * @template R1
 * @implements Implementation<L1, R1>
 * @psalm-immutable
 * @internal
 */
final class Defer implements Implementation
{
    /** @var callable(): Either<L1, R1> */
    private $deferred;
    /** @var ?Either<L1, R1> */
    private ?Either $value = null;

    /**
     * @param callable(): Either<L1, R1> $deferred
     */
    public function __construct($deferred)
    {
        $this->deferred = $deferred;
    }

    #[\Override]
    public function map(callable $map): self
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->map($map));
    }

    #[\Override]
    public function flatMap(callable $map): Either
    {
        $captured = $this->capture();

        return Either::defer(static fn() => self::detonate($captured)->flatMap($map));
    }

    #[\Override]
    public function leftMap(callable $map): self
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->leftMap($map));
    }

    #[\Override]
    public function match(callable $right, callable $left)
    {
        return $this->unwrap()->match($right, $left);
    }

    #[\Override]
    public function otherwise(callable $otherwise): Either
    {
        $captured = $this->capture();

        return Either::defer(static fn() => self::detonate($captured)->otherwise($otherwise));
    }

    #[\Override]
    public function filter(callable $predicate, callable $otherwise): Implementation
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->filter($predicate, $otherwise));
    }

    #[\Override]
    public function maybe(): Maybe
    {
        $captured = $this->capture();

        return Maybe::defer(static fn() => self::detonate($captured)->maybe());
    }

    #[\Override]
    public function attempt(callable $error): Attempt
    {
        $captured = $this->capture();

        return Attempt::defer(static fn() => self::detonate($captured)->attempt($error));
    }

    /**
     * @return Either<L1, R1>
     */
    #[\Override]
    public function memoize(): Either
    {
        return $this->unwrap();
    }

    #[\Override]
    public function flip(): self
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->flip());
    }

    #[\Override]
    public function eitherWay(callable $right, callable $left): Either
    {
        $captured = $this->capture();

        return Either::defer(static fn() => self::detonate($captured)->eitherWay($right, $left));
    }

    /**
     * @return Either<L1, R1>
     */
    private function unwrap(): Either
    {
        /**
         * @psalm-suppress InaccessibleProperty
         * @psalm-suppress ImpureFunctionCall
         */
        return $this->value ??= ($this->deferred)()->memoize();
    }

    /**
     * @return array{\WeakReference<self<L1, R1>>, callable(): Either<L1, R1>}
     */
    private function capture(): array
    {
        /** @psalm-suppress ImpureMethodCall */
        return [
            \WeakReference::create($this),
            $this->deferred,
        ];
    }

    /**
     * @template A
     * @template B
     *
     * @param array{\WeakReference<self<A, B>>, callable(): Either<A, B>} $captured
     *
     * @return Either<A, B>
     */
    private static function detonate(array $captured): Either
    {
        [$ref, $deferred] = $captured;
        $self = $ref->get();

        if (\is_null($self)) {
            return $deferred();
        }

        return $self->unwrap();
    }
}
