<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\InvalidArgumentException;

class Set implements SetInterface
{
    use Type;

    private $type;
    private $spec;
    private $values;

    /**
     * {@inheritdoc}
     */
    public function __construct(string $type)
    {
        $this->type = new Str($type);
        $this->spec = $this->getSpecificationFor($type);
        $this->values = new Stream($type);
    }

    public static function of(string $type, ...$values): self
    {
        return array_reduce(
            $values,
            static function(self $self, $value): self {
                return $self->add($value);
            },
            new self($type)
        );
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
    public function toPrimitive()
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
    public function key()
    {
        return $this->values->key();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->values->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->values->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
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
            $set->reduce(
                $this->values->clear(),
                function(Stream $carry, $value): Stream {
                    return $carry->add($value);
                }
            )
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
            $set->reduce(
                $this->values->clear(),
                function(Stream $carry, $value): Stream {
                    return $carry->add($value);
                }
            )
        );

        return $newSet;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(SetInterface $set): bool
    {
        $this->validate($set);

        return $this->values->equals(
            $set->reduce(
                $this->values->clear(),
                function(Stream $carry, $value): Stream {
                    return $carry->add($value);
                }
            )
        );
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
            function(Map $carry, $key, StreamInterface $values): Map {
                return $carry->put(
                    $key,
                    $values->reduce(
                        $this->clear(),
                        function(Set $carry, $value): Set {
                            return $carry->add($value);
                        }
                    )
                );
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
            function(self $carry, $value) use ($function): self {
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

        return (new Map('bool', SetInterface::class))
            ->put(true, $truthy)
            ->put(false, $falsy);
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
            function(self $carry, $value): self {
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
