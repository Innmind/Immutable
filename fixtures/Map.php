<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\{
    Set,
    Set\Value,
};
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
            $pairs = $this->generate($size->unwrap());

            if ($immutable) {
                yield Set\Value::immutable($this->wrap(...$pairs));
            } else {
                yield Set\Value::mutable(fn() => $this->wrap(...$pairs));
            }
        }
    }

    /**
     * @return array{0: list<Value>, 1: list<Value>}
     */
    private function generate(int $size): array
    {
        return [
            \iterator_to_array($this->keys->take($size)->values()),
            \iterator_to_array($this->values->take($size)->values()),
        ];
    }

    /**
     * @param list<Value> $keys
     * @param list<Value> $values
     */
    private function wrap(array $keys, array $values): Structure
    {
        $map = Structure::of($this->keyType, $this->valueType);

        foreach ($keys as $key) {
            $map = ($map)($key->unwrap(), \current($values)->unwrap());
            \next($values);
        }

        return $map;
    }
}
