<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Attempt;

use Innmind\Immutable\{
    Maybe,
    Either
};

/**
 * @template R1
 * @implements Implementation<R1>
 * @psalm-immutable
 * @internal
 */
final class Defer implements Implementation
{
    /** @var ?Implementation<R1> */
    private ?Implementation $value = null;

    /**
     * @param \Closure(): Implementation<R1> $deferred
     */
    public function __construct(private \Closure $deferred)
    {
    }

    #[\Override]
    public function map(callable $map): self
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->map($map));
    }

    #[\Override]
    public function flatMap(callable $map): self
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->flatMap($map));
    }

    #[\Override]
    public function guard(callable $map): self
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->guard($map));
    }

    #[\Override]
    public function guardError(): self
    {
        return $this;
    }

    #[\Override]
    public function match(callable $result, callable $error)
    {
        return $this->unwrap()->match($result, $error);
    }

    #[\Override]
    public function mapError(callable $map): self
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->mapError($map));
    }

    #[\Override]
    public function recover(callable $recover): self
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->recover($recover));
    }

    #[\Override]
    public function xrecover(callable $recover): self
    {
        $captured = $this->capture();

        return new self(static fn() => self::detonate($captured)->xrecover($recover));
    }

    #[\Override]
    public function maybe(): Maybe
    {
        $captured = $this->capture();

        return Maybe::defer(static fn() => self::detonate($captured)->maybe());
    }

    #[\Override]
    public function either(): Either
    {
        $captured = $this->capture();

        return Either::defer(static fn() => self::detonate($captured)->either());
    }

    #[\Override]
    public function memoize(): Implementation
    {
        return $this->unwrap();
    }

    #[\Override]
    public function eitherWay(
        callable $result,
        callable $error,
    ): self {
        $captured = $this->capture();

        return new self(
            static fn() => self::detonate($captured)->eitherWay($result, $error),
        );
    }

    /**
     * @return Implementation<R1>
     */
    private function unwrap(): Implementation
    {
        /**
         * @psalm-suppress InaccessibleProperty
         * @psalm-suppress ImpureFunctionCall
         */
        return $this->value ??= ($this->deferred)();
    }

    /**
     * @return array{\WeakReference<self<R1>>, callable(): Implementation<R1>}
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
     *
     * @param array{\WeakReference<self<A>>, callable(): Implementation<A>} $captured
     *
     * @return Implementation<A>
     */
    private static function detonate(array $captured): Implementation
    {
        [$ref, $deferred] = $captured;
        $self = $ref->get();

        if (\is_null($self)) {
            return $deferred();
        }

        return $self->unwrap();
    }
}
