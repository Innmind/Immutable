<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * Concatenate all elements with the given separator
 *
 * @psalm-pure
 *
 * @param Set<string>|Sequence<string> $structure
 */
function join(string $separator, $structure): Str
{
    return Str::of(\implode($separator, $structure->toList()));
}
