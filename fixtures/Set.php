<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\{
    Set as DataSet,
    Set\Value,
    Set\Dichotomy,
};
use Innmind\Immutable\Set as Structure;
use function Innmind\Immutable\first;

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
                yield DataSet\Value::immutable(
                    $this->wrap($values),
                    $this->shrink(false, $this->wrap($values)),
                );
            } else {
                yield DataSet\Value::mutable(
                    fn() => $this->wrap($values),
                    $this->shrink(true, $this->wrap($values)),
                );
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

    private function shrink(bool $mutable, Structure $set): ?Dichotomy
    {
        if ($set->empty()) {
            return null;
        }

        return new Dichotomy(
            $this->removeHalfTheStructure($mutable, $set),
            $this->removeHeadElement($mutable, $set),
        );
    }

    private function removeHalfTheStructure(bool $mutable, Structure $set): callable
    {
        // we round half down otherwise a set of 1 element would be shrunk to a
        // set of 1 element resulting in a infinite recursion
        $numberToDrop = (int) \round($set->size() / 2, \PHP_ROUND_HALF_DOWN);
        $shrinked = $set;

        for ($i = 0; $i < $numberToDrop; $i++) {
            $shrinked = $shrinked->remove(first($shrinked));
        }

        if ($mutable) {
            return fn(): Value => Value::mutable(
                fn() => $shrinked,
                $this->shrink(true, $shrinked),
            );
        }

        return fn(): Value => Value::immutable(
            $shrinked,
            $this->shrink(false, $shrinked),
        );
    }

    private function removeHeadElement(bool $mutable, Structure $set): callable
    {
        $shrinked = $set->remove(first($set));

        if ($mutable) {
            return fn(): Value => Value::mutable(
                fn() => $shrinked,
                $this->shrink(true, $shrinked),
            );
        }

        return fn(): Value => Value::immutable(
            $shrinked,
            $this->shrink(false, $shrinked),
        );
    }
}
