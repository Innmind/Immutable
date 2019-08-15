<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\InvalidArgumentException;

class Set implements SetInterface
{
    private $type;
    private $spec;
    private $values;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $type)
    {
        $this->type = new Str($type);
        $this->spec = Type::of($type);
        $this->values = new Stream($type);
    }

    public static function of(string $type, ...$values): self
    {
        $self = new self($type);
        $self->values = Stream::of($type, ...$values)->distinct();

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): Str
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return $this->values->size();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->values->size();
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive(): array
    {
        return $this->values->toPrimitive();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->values->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key(): int
    {
        return $this->values->key();
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->values->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->values->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->values->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function intersect(SetInterface $set): SetInterface
    {
        $this->validate($set);

        $newSet = clone $this;
        $newSet->values = $this->values->intersect(
            Stream::of((string) $this->type, ...$set)
        );

        return $newSet;
    }

    /**
     * {@inheritdoc}
     */
    public function add($element): SetInterface
    {
        $this->spec->validate($element);

        if ($this->contains($element)) {
            return $this;
        }

        $set = clone $this;
        $set->values = $this->values->add($element);

        return $set;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element): bool
    {
        return $this->values->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($element): SetInterface
    {
        if (!$this->contains($element)) {
            return $this;
        }

        $index = $this->values->indexOf($element);
        $set = clone $this;
        $set->values = $this
            ->values
            ->clear()
            ->append($this->values->slice(0, $index))
            ->append($this->values->slice($index + 1, $this->size()));

        return $set;
    }

    /**
     * {@inheritdoc}
     */
    public function diff(SetInterface $set): SetInterface
    {
        $this->validate($set);

        $newSet = clone $this;
        $newSet->values = $this->values->diff(
            Stream::of((string) $this->type, ...$set)
        );

        return $newSet;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(SetInterface $set): bool
    {
        $this->validate($set);

        if ($this->size() !== $set->size()) {
            return false;
        }

        return $this->intersect($set)->size() === $this->size();
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): SetInterface
    {
        $set = clone $this;
        $set->values = $this->values->filter($predicate);

        return $set;
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): SetInterface
    {
        $this->values->foreach($function);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): MapInterface
    {
        $map = $this->values->groupBy($discriminator);

        return $map->reduce(
            new Map((string) $map->keyType(), SetInterface::class),
            function(MapInterface $carry, $key, StreamInterface $values): MapInterface {
                $set = $this->clear();
                $set->values = $values;

                return $carry->put($key, $set);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): SetInterface
    {
        return $this->reduce(
            $this->clear(),
            function(SetInterface $carry, $value) use ($function): SetInterface {
                return $carry->add($function($value));
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): MapInterface
    {
        $truthy = $this->clear();
        $falsy = $this->clear();
        $partitions = $this->values->partition($predicate);
        $truthy->values = $partitions->get(true);
        $falsy->values = $partitions->get(false);

        return Map::of('bool', SetInterface::class)
            (true, $truthy)
            (false, $falsy);
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): Str
    {
        return $this->values->join($separator);
    }

    /**
     * {@inheritdoc}
     */
    public function sort(callable $function): StreamInterface
    {
        return $this->values->sort($function);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(SetInterface $set): SetInterface
    {
        $this->validate($set);

        return $set->reduce(
            $this,
            function(SetInterface $carry, $value): SetInterface {
                return $carry->add($value);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->values->reduce($carry, $reducer);
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): SetInterface
    {
        $self = clone $this;
        $self->values = $this->values->clear();

        return $self;
    }

    public function empty(): bool
    {
        return $this->values->empty();
    }

    /**
     * Make sure the set is compatible with the current one
     *
     * @param SetInterface $set
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    private function validate(SetInterface $set)
    {
        if (!$set->type()->equals($this->type)) {
            throw new InvalidArgumentException(
                'The 2 sets does not reference the same type'
            );
        }
    }
}
