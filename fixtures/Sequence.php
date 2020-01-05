<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Immutable;

use Innmind\BlackBox\Set;
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
            if ($immutable) {
                yield Set\Value::immutable($this->generate($size->unwrap()));
            } else {
                yield Set\Value::mutable(fn() => $this->generate($size->unwrap()));
            }
        }
    }

    private function generate(int $size): Structure
    {
        $sequence = Structure::of($this->type);
        $values = $this->set->take($size)->values();

        while ($sequence->size() < $size) {
            $sequence = ($sequence)($values->current()->unwrap());
            $values->next();
        }

        return $sequence;
    }
}
