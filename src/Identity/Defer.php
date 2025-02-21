<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Identity;

use Innmind\Immutable\{
    Identity,
    Sequence,
};

/**
 * @psalm-immutable
 * @template T
 * @implements Implementation<T>
 */
final class Defer implements Implementation
{
    /** @var callable(): T */
    private $value;
    private bool $loaded = false;
    /** @var ?T */
    private mixed $computed = null;

    /**
     * @param callable(): T $value
     */
    public function __construct(callable $value)
    {
        $this->value = $value;
    }

    #[\Override]
    public function map(callable $map): self
    {
        $captured = $this->capture();

        /**
         * @psalm-suppress ImpureFunctionCall
         * @psalm-suppress MixedArgument
         */
        return new self(static fn() => $map(self::detonate($captured)));
    }

    #[\Override]
    public function flatMap(callable $map): Identity
    {
        $captured = $this->capture();

        /**
         * @psalm-suppress ImpureFunctionCall
         * @psalm-suppress MixedArgument
         */
        return Identity::defer(static fn() => $map(self::detonate($captured))->unwrap());
    }

    #[\Override]
    public function toSequence(): Sequence
    {
        $captured = $this->capture();

        /** @psalm-suppress ImpureFunctionCall */
        return Sequence::defer((static fn() => yield self::detonate($captured))());
    }

    #[\Override]
    public function unwrap(): mixed
    {
        if ($this->loaded) {
            /** @var T */
            return $this->computed;
        }

        /**
         * @psalm-suppress InaccessibleProperty
         * @psalm-suppress ImpureFunctionCall
         */
        $this->computed = ($this->value)();
        /** @psalm-suppress InaccessibleProperty */
        $this->loaded = true;

        return $this->computed;
    }

    /**
     * @return array{\WeakReference<Implementation<T>>, callable(): T}
     */
    private function capture(): array
    {
        /** @psalm-suppress ImpureMethodCall */
        return [
            \WeakReference::create($this),
            $this->value,
        ];
    }

    /**
     * @template V
     *
     * @param array{\WeakReference<Implementation<V>>, callable(): V} $captured
     *
     * @return V
     */
    private static function detonate(array $captured): mixed
    {
        [$ref, $value] = $captured;
        $self = $ref->get();

        if (\is_null($self)) {
            return $value();
        }

        return $self->unwrap();
    }
}
