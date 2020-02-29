<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\{
    Set,
    Set\Value,
    Set\Dichotomy,
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
                yield Set\Value::immutable(
                    $this->wrap($values),
                    $this->shrink(false, $this->wrap($values)),
                );
            } else {
                yield Set\Value::mutable(
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

    private function shrink(bool $mutable, Structure $sequence): ?Dichotomy
    {
        if ($sequence->empty()) {
            return null;
        }

        return new Dichotomy(
            $this->removeHalfTheStructure($mutable, $sequence),
            $this->removeTailElement($mutable, $sequence),
        );
    }

    private function removeHalfTheStructure(bool $mutable, Structure $sequence): callable
    {
        // we round half down otherwise a sequence of 1 element would be shrunk
        // to a sequence of 1 element resulting in a infinite recursion
        $numberToDrop = (int) \round($sequence->size() / 2, \PHP_ROUND_HALF_DOWN);
        $shrinked = $sequence->dropEnd($numberToDrop);

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

    private function removeTailElement(bool $mutable, Structure $sequence): callable
    {
        $shrinked = $sequence->dropEnd(1);

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
