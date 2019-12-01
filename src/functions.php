<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @throws TypeError
 */
function assertSet(string $type, Set $set, int $position = null): void
{
    $message = '';

    if (is_int($position)) {
        $message = "Argument $position must be of type Set<$type>, Set<{$set->type()}> given";
    }

    if (!$set->isOfType($type)) {
        throw new \TypeError($message);
    }
}

/**
 * @throws TypeError
 */
function assertMap(string $key, string $value, Map $map, int $position = null): void
{
    $message = '';

    if (is_int($position)) {
        $message = "Argument $position must be of type Map<$key, $value>, Map<{$map->keyType()}, {$map->valueType()}> given";
    }

    if (!$map->isOfType($key, $value)) {
        throw new \TypeError($message);
    }
}

/**
 * @throws TypeError
 */
function assertStream(string $type, Stream $stream, int $position = null): void
{
    $message = '';

    if (is_int($position)) {
        $message = "Argument $position must be of type Stream<$type>, Stream<{$stream->type()}> given";
    }

    if (!$stream->isOfType($type)) {
        throw new \TypeError($message);
    }
}
