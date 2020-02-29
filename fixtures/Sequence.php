<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\{
    Set,
    Set\Value,
};
use Innmind\Immutable\Sequence as Structure;

/**
 * {@inheritdoc}
 * @template I
 */
final class Sequence implements Set
{
    private $type;
    private $set;
    private $sizes;

    public function __construct(string $type, Set $set, Set\Integers $sizes = null)
    {
        $this->type = $type;
        $this->set = $set;
        $this->sizes = ($sizes ?? Set\Integers::between(0, 100))->take(100);
    }

    /**
     * @return Set<Structure<I>>
     */
    public static function of(string $type, Set $set, Set\Integers $sizes = null): self
    {
        return new self($type, $set, $sizes);
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
        throw new \LogicException('Sequence set can\'t be filtered, underlying set must be filtered beforehand');
    }

    /**
     * @return \Generator<Set\Value<Structure<I>>>
     */
    public function values(): \Generator
    {
        $immutable = $this->set->values()->current()->isImmutable();

        foreach ($this->sizes->values() as $size) {
            $values = $this->generate($size->unwrap());

            if ($immutable) {
                yield Set\Value::immutable($this->wrap($values));
            } else {
                yield Set\Value::mutable(fn() => $this->wrap($values));
            }
        }
    }

    /**
     * @return list<Value>
     */
    private function generate(int $size): array
    {
        return \iterator_to_array($this->set->take($size)->values());
    }

    /**
     * @param list<Value> $values
     */
    private function wrap(array $values): Structure
    {
        return Structure::of(
            $this->type,
            ...\array_map(
                static fn(Value $value) => $value->unwrap(),
                $values,
            ),
        );
    }
}
