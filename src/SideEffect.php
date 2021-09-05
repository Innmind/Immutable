<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * This class is here to mark methods that will perform side effects even though
 * the structure is immutable
 *
 * @psalm-immutable
 */
final class SideEffect
{
}
