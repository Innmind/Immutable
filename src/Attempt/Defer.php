<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Attempt;

use Innmind\Immutable\{
    Attempt,
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
    /** @var callable(): Attempt<R1> */
    private $deferred;
    /** @var ?Attempt<R1> */
    private ?Attempt $value = null;

    /**
     * @param callable(): Attempt<R1> $deferred
     */
    public function __construct(callable $deferred)
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
    public function flatMap(callable $map): Attempt
    {
        $captured = $this->capture();

        return Attempt::defer(static fn() => self::detonate($captured)->flatMap($map));
    }

    #[\Override]
    public function match(callable $result, callable $error)
    {
        return $this->unwrap()->match($result, $error);
    }

    #[\Override]
    public function recover(callable $recover): Attempt
    {
        $captured = $this->capture();

        return Attempt::defer(static fn() => self::detonate($captured)->recover($recover));
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

    /**
     * @return Attempt<R1>
     */
    #[\Override]
    public function memoize(): Attempt
    {
        return $this->unwrap();
    }

    #[\Override]
    public function eitherWay(callable $result, callable $error): Attempt
    {
        $captured = $this->capture();

        return Attempt::defer(
            static fn() => self::detonate($captured)->eitherWay($result, $error),
        );
    }

    /**
     * @return Attempt<R1>
     */
    private function unwrap(): Attempt
    {
        /**
         * @psalm-suppress InaccessibleProperty
         * @psalm-suppress ImpureFunctionCall
         */
        return $this->value ??= ($this->deferred)()->memoize();
    }

    /**
     * @return array{\WeakReference<self<R1>>, callable(): Attempt<R1>}
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
     * @param array{\WeakReference<self<A>>, callable(): Attempt<A>} $captured
     *
     * @return Attempt<A>
     */
    private static function detonate(array $captured): Attempt
    {
        [$ref, $deferred] = $captured;
        $self = $ref->get();

        if (\is_null($self)) {
            return $deferred();
        }

        return $self->unwrap();
    }
}
