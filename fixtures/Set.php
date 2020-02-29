<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\{
    Set as DataSet,
    Set\Value,
};
use Innmind\Immutable\Set as Structure;

/**
 * {@inheritdoc}
 * @template I
 */
final class Set implements DataSet
{
    private $type;
    private $set;
    private $sizes;

    public function __construct(string $type, DataSet $set, DataSet\Integers $sizes = null)
    {
        $this->type = $type;
        $this->set = $set;
        $this->sizes = ($sizes ?? DataSet\Integers::between(0, 100))->take(100);
    }

    /**
     * @return Set<Structure<I>>
     */
    public static function of(string $type, DataSet $set, DataSet\Integers $sizes = null): self
    {
        return new self($type, $set, $sizes);
    }

    public function take(int $size): DataSet
    {
        $self = clone $this;
        $self->sizes = $this->sizes->take($size);

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): DataSet
    {
        throw new \LogicException('Set set can\'t be filtered, underlying set must be filtered beforehand');
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
                yield DataSet\Value::immutable($this->wrap($values));
            } else {
                yield DataSet\Value::mutable(fn() => $this->wrap($values));
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
