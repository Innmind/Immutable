<?php
declare(strict_types = 1);

use Innmind\Immutable\Str;
use Properties\Innmind\Immutable\Str as Properties;
use Innmind\BlackBox\{
    Set,
    Prove,
};

return static function(Prove $prove) {
    $strings = Set::strings()
        ->between(0, 100_00)
        ->map(Str::of(...))
        ->map(static fn($str) => $str->toEncoding(Str\Encoding::ascii))
        ->map(static fn($str) => static fn() => $str);

    yield $prove->properties(
        'Str',
        Properties::properties(),
        $strings,
    )->tag(\Innmind\BlackBox\Tag::wip);

    foreach (Properties::list() as $property) {
        yield $prove->property(
            $property,
            $strings,
        )->tag(\Innmind\BlackBox\Tag::wip);
    }
};
