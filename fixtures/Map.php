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
        throw new \LogicException('Map set can\'t be filtered, underlying sets must be filtered beforehand');
    }

    /**
     * @return \Generator<Set\Value<Structure<I, J>>>
     */
    public function values(): \Generator
    {
        $immutable = $this->keys->values()->current()->isImmutable() &&
            $this->values->values()->current()->isImmutable();

        foreach ($this->sizes->values() as $size) {
            if ($immutable) {
                yield Set\Value::immutable($this->generate($size->unwrap()));
            } else {
                yield Set\Value::mutable(fn() => $this->generate($size->unwrap()));
            }
        }
    }

    private function generate(int $size): Structure
    {
        $map = Structure::of($this->keyType, $this->valueType);
        $keys = $this->keys->take($size)->values();
        $values = $this->values->take($size)->values();

        while ($map->size() < $size) {
            $map = ($map)(
                $keys->current()->unwrap(),
                $values->current()->unwrap(),
            );
            $keys->next();
            $values->next();
        }

        return $map;
    }
}
