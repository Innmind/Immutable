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

    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self(fn() => $map($this->load()));
    }

    public function flatMap(callable $map): Identity
    {
        /** @psalm-suppress ImpureFunctionCall */
        return Identity::lazy(fn() => $map($this->load())->unwrap());
    }

    public function toSequence(): Sequence
    {
        /** @psalm-suppress ImpureFunctionCall */
        return Sequence::defer((fn() => yield $this->load())());
    }

    public function unwrap(): mixed
    {
        return $this->load();
    }

    /**
     * @return T
     */
    private function load(): mixed
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
}
