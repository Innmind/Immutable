<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
enum Encoding
{
    case utf8;
    case utf16;
    case utf32;
    case ascii;

    /**
     * @return non-empty-string
     */
    public function toString(): string
    {
        return match ($this) {
            self::utf8 => 'UTF-8',
            self::utf16 => 'UTF-16',
            self::utf32 => 'UTF-32',
            self::ascii => 'ASCII',
        };
    }
}
