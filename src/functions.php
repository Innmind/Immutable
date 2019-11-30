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
        $message = "Argument $position must be of type Set<$type>";
    }

    if ((string) $set->type() !== $type) {
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
        $message = "Argument $position must be of type Map<$key, $value>";
    }

    if (
        (string) $map->keyType() !== $key ||
        (string) $map->valueType() !== $value
    ) {
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
        $message = "Argument $position must be of type Stream<$type>";
    }

    if ((string) $stream->type() !== $type) {
        throw new \TypeError($message);
    }
}
