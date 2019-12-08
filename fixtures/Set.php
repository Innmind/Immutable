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
    private $predicate;

    public function __construct(string $type, DataSet $set, DataSet\Integers $sizes = null)
    {
        $this->type = $type;
        $this->set = $set;
        $this->sizes = ($sizes ?? DataSet\Integers::between(0, 100))->take(100);
        $this->predicate = static function(): bool {
            return true;
        };
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
     * @return \Generator<Structure<I>>
     */
    public function values(): \Generator
    {
        foreach ($this->sizes->values() as $size) {
            $set = Structure::of($this->type);
            $values = $this->set->take($size)->values();

            while ($set->size() < $size) {
                $set = ($set)($values->current());
                $values->next();
            }

            if (!($this->predicate)($set)) {
                continue;
            }

            yield $set;
        }
    }
}
