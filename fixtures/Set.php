<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\Set as DataSet;
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
        foreach ($this->sizes->values() as $size) {
            yield DataSet\Value::immutable($this->generate($size->unwrap()));
        }
    }

    private function generate(int $size): Structure
    {
        $set = Structure::of($this->type);
        $values = $this->set->take($size)->values();

        while ($set->size() < $size) {
            $set = ($set)($values->current()->unwrap());
            $values->next();
        }

        return $set;
    }
}
