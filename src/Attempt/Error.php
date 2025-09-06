<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Attempt;

use Innmind\Immutable\{
    Maybe,
    Either,
};

/**
 * @template R1
 * @implements Implementation<R1>
 * @psalm-immutable
 * @internal
 */
final class Error implements Implementation
{
    public function __construct(
        private \Throwable $value,
    ) {
    }

    /**
     * @template T
     *
     * @param callable(R1): T $map
     *
     * @return self<T>
     */
    #[\Override]
    public function map(callable $map): self
    {
        /** @var self<T> */
        return $this;
    }

    #[\Override]
    public function flatMap(
        callable $map,
        callable $exfiltrate,
    ): self {
        return $this;
    }

    #[\Override]
    public function match(callable $result, callable $error)
    {
        /** @psalm-suppress ImpureFunctionCall */
        return $error($this->value);
    }

    #[\Override]
    public function mapError(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return new self($map($this->value));
    }

    #[\Override]
    public function recover(
        callable $recover,
        callable $exfiltrate,
    ): Implementation {
        /** @psalm-suppress ImpureFunctionCall */
        return $exfiltrate($recover($this->value));
    }

    #[\Override]
    public function maybe(): Maybe
    {
        return Maybe::nothing();
    }

    #[\Override]
    public function either(): Either
    {
        return Either::left($this->value);
    }

    #[\Override]
    public function memoize(callable $exfiltrate): self
    {
        return $this;
    }

    #[\Override]
    public function eitherWay(
        callable $result,
        callable $error,
        callable $exfiltrate,
    ): Implementation {
        /** @psalm-suppress ImpureFunctionCall */
        return $exfiltrate($error($this->value));
    }
}
