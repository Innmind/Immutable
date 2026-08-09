<?php
declare(strict_types = 1);

use Innmind\Immutable\Str;
use Properties\Innmind\Immutable\Str as Properties;
use Innmind\BlackBox\{
    Set,
    Prove,
};

return static function(Prove $prove) {
    $ascii = Set::strings()
        ->between(0, 100_00)
        ->map(Str::of(...))
        ->map(static fn($str) => $str->toEncoding(Str\Encoding::ascii))
        ->map(static fn($str) => static fn() => $str);
    $utf8 = Set::strings()
        ->madeOf(
            Set::strings()->chars()->alphanumerical(),
            Set::strings()->unicode()->emoticons(),
            Set::strings()->unicode()->alchemicalSymbols(),
            Set::strings()->unicode()->mathematicalOperators(),
            Set::strings()->unicode()->currencySymbols(),
            Set::strings()->unicode()->generalPunctuation(),
            Set::strings()->unicode()->arabic(),
            Set::strings()->unicode()->hebrew(),
            Set::strings()->unicode()->cyrillic(),
            Set::strings()->unicode()->controlCharater(),
        )
        ->between(0, 100_000)
        ->map(Str::of(...))
        ->map(static fn($str) => $str->toEncoding(Str\Encoding::utf8))
        ->map(static fn($str) => static fn() => $str);
    $strings = Set::either($ascii, $utf8);

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
