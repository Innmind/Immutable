<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\Set;
use Innmind\Immutable\Map as Structure;

/**
 * {@inheritdoc}
 * @template I
 * @template J
 */
final class Map implements Set
{
    private $keyType;
    private $valueType;
    private $keys;
    private $values;
    private $sizes;
    private $predicate;

    public function __construct(
        string $keyType,
        string $valueType,
        Set $keys,
        Set $values,
        Set\Integers $sizes = null
    ) {
        $this->keyType = $keyType;
        $this->valueType = $valueType;
        $this->keys = $keys;
        $this->values = $values;
        $this->sizes = ($sizes ?? Set\Integers::between(0, 100))->take(100);
        $this->predicate = static function(): bool {
            return true;
        };
    }

    /**
     * @return Set<Structure<I, J>>
     */
    public static function of(
        string $keyType,
        string $valueType,
        Set $keys,
        Set $values,
        Set\Integers $sizes = null
    ): self {
        return new self($keyType, $valueType, $keys, $values, $sizes);
    }

    public function take(int $size): Set
    {
        $self = clone $this;
        $self->sizes = $this->sizes->take($size);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): Set
    {
        $self = clone $this;
        $self->predicate = function($value) use ($predicate): bool {
            if (!($this->predicate)($value)) {
                return false;
            }

            return $predicate($value);
        };

        return $self;
    }

    /**
     * @return \Generator<Structure<I, J>>
     */
    public function values(): \Generator
    {
        foreach ($this->sizes->values() as $size) {
            $map = Structure::of($this->keyType, $this->valueType);
            $keys = $this->keys->take($size)->values();
            $values = $this->values->take($size)->values();

            while ($map->size() < $size) {
                $map = ($map)(
                    $keys->current(),
                    $values->current(),
                );
                $keys->next();
                $values->next();
            }

            if (!($this->predicate)($map)) {
                continue;
            }

            yield $map;
        }
    }
}
