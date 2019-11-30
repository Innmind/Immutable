<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\{
    DomainException,
    RegexException
};

final class RegExp
{
    private string $pattern;

    public function __construct(string $pattern)
    {
        if (@\preg_match($pattern, '') === false) {
            throw new DomainException($pattern, \preg_last_error());
        }

        $this->pattern = $pattern;
    }

    public static function of(string $pattern): self
    {
        return new self($pattern);
    }

    public function matches(Str $string): bool
    {
        $value = \preg_match($this->pattern, (string) $string);

        if ($value === false) {
            throw new RegexException('', \preg_last_error());
        }

        return (bool) $value;
    }


    /**
     * @return Map<scalar, Str>
     */
    public function capture(Str $string): Map
    {
        $matches = [];
        $value = \preg_match($this->pattern, (string) $string, $matches);

        if ($value === false) {
            throw new RegexException('', \preg_last_error());
        }

        $map = new Map('scalar', Str::class);

        foreach ($matches as $key => $match) {
            $map = $map->put(
                $key,
                new Str(
                    (string) $match,
                    (string) $string->encoding()
                )
            );
        }

        return $map;
    }

    public function __toString(): string
    {
        return $this->pattern;
    }
}
