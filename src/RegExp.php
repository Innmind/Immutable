<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\{
    DomainException,
    RegexException,
};

final class RegExp
{
    private string $pattern;

    private function __construct(string $pattern)
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

    /**
     * @throws RegexException
     */
    public function matches(Str $string): bool
    {
        $value = \preg_match($this->pattern, $string->toString());

        if ($value === false) {
            throw new RegexException('', \preg_last_error());
        }

        return (bool) $value;
    }

    /**
     * @throws RegexException
     *
     * @return Map<scalar, Str>
     */
    public function capture(Str $string): Map
    {
        $matches = [];
        $value = \preg_match($this->pattern, $string->toString(), $matches);

        if ($value === false) {
            throw new RegexException('', \preg_last_error());
        }

        /** @var Map<scalar, Str> */
        $map = Map::of('scalar', Str::class);

        foreach ($matches as $key => $match) {
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
