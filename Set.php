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

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->spec = $this->getSpecFor($type);
        $this->values = new Sequence;
    }

    /**
     * {@inheritdoc}
     */
    public function type(): string
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
            new Sequence(...$set->toPrimitive())
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
        $set->values = (new Sequence)
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
            new Sequence(...$set->toPrimitive())
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
            new Sequence(...$set->toPrimitive())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function filter(\Closure $predicate): SetInterface
    {
        $set = clone $this;
        $set->values = $this->values->filter($predicate);

        return $set;
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(\Closure $function): SetInterface
    {
        $this->values->foreach($function);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(\Closure $discriminator): MapInterface
    {
        return $this->values->groupBy($discriminator);
    }

    /**
     * {@inheritdoc}
     */
    public function map(\Closure $function): SetInterface
    {
        $set = clone $this;
        $set->values = $this->values->map($function);

        return $set;
    }

    /**
     * {@inheritdoc}
     */
    public function partition(\Closure $predicate): SequenceInterface
    {
        $truthy = clone $this;
        $falsy = clone $this;
        $partitions = $this->values->partition($predicate);
        $truthy->values = $partitions->get(0);
        $falsy->values = $partitions->get(1);

        return new Sequence($truthy, $falsy);
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): StringPrimitive
    {
        return $this->values->join($separator);
    }

    /**
     * {@inheritdoc}
     */
    public function sort(\Closure $function): SequenceInterface
    {
        return $this->values->sort($function);
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
        if ($set->type() !== $this->type) {
            throw new InvalidArgumentException(
                'The 2 sets does not reference the same type'
            );
        }
    }
}
