<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @throws \TypeError
 */
function assertSet(string $type, Set $set, int $position): void
{
    if (!$set->isOfType($type)) {
        throw new \TypeError("Argument $position must be of type Set<$type>, Set<{$set->type()}> given");
    }
}

/**
 * @throws \TypeError
 */
function assertMap(string $key, string $value, Map $map, int $position): void
{
    if (!$map->isOfType($key, $value)) {
        throw new \TypeError("Argument $position must be of type Map<$key, $value>, Map<{$map->keyType()}, {$map->valueType()}> given");
    }
}

/**
 * @throws \TypeError
 */
function assertSequence(string $type, Sequence $stream, int $position): void
{
    if (!$stream->isOfType($type)) {
        throw new \TypeError("Argument $position must be of type Sequence<$type>, Sequence<{$stream->type()}> given");
    }
}
