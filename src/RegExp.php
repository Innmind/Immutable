<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\{
    LogicException,
    InvalidRegex,
};

/**
 * @psalm-immutable
 */
final class RegExp
{
    private string $pattern;

    private function __construct(string $pattern)
    {
        if (@\preg_match($pattern, '') === false) {
            /** @psalm-suppress ImpureFunctionCall */
            throw new LogicException($pattern, \preg_last_error());
        }

        $this->pattern = $pattern;
    }

    /**
     * @psalm-pure
     */
    public static function of(string $pattern): self
    {
        return new self($pattern);
    }

    /**
     * @throws InvalidRegex
     */
    public function matches(Str $string): bool
    {
        $value = \preg_match($this->pattern, $string->toString());

        if ($value === false) {
            /** @psalm-suppress ImpureFunctionCall */
            throw new InvalidRegex('', \preg_last_error());
        }

        return (bool) $value;
    }

    /**
     * @throws InvalidRegex
     *
     * @return Map<int|string, Str>
     */
    public function capture(Str $string): Map
    {
        $matches = [];
        $value = \preg_match($this->pattern, $string->toString(), $matches);

        if ($value === false) {
            /** @psalm-suppress ImpureFunctionCall */
            throw new InvalidRegex('', \preg_last_error());
        }

        /** @var Map<int|string, Str> */
        $map = Map::of();

        foreach ($matches as $key => $match) {
            /** @psalm-suppress RedundantCast Don't trust the types of preg_match */
            $map = ($map)(
                $key,
                Str::of(
                    (string) $match,
                    $string->encoding()->toString(),
                )
            );
        }

        return $map;
    }

    public function toString(): string
    {
        return $this->pattern;
    }
}
