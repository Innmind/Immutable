<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Set;

use Innmind\Immutable\{
    Map,
    Sequence,
    Set,
    Type,
    ValidateArgument,
    Str,
};

final class Primitive implements Implementation
{
    private string $type;
    private ValidateArgument $validate;
    private Sequence $values;

    public function __construct(string $type, ...$values)
    {
        $this->type = $type;
        $this->validate = Type::of($type);
        $this->values = Sequence::of($type, ...$values)->distinct();
    }

    public function isOfType(string $type): bool
    {
        return $this->type === $type;
    }

    public function type(): string
    {
        return $this->type;
    }

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

    public function toArray(): array
    {
        return $this->values->toArray();
    }

    public function intersect(Implementation $set): self
    {
        $self = $this->clear();
        $self->values = $this->values->intersect(
            Sequence::of($this->type, ...$set->toArray())
        );

        return $self;
    }

    public function add($element): self
    {
        if ($this->contains($element)) {
            return $this;
        }

        $set = clone $this;
        $set->values = $this->values->add($element);

        return $set;
    }

    public function contains($element): bool
    {
        return $this->values->contains($element);
    }

    public function remove($element): self
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

    public function diff(Implementation $set): self
    {
        $self = clone $this;
        $self->values = $this->values->diff(
            Sequence::of($this->type, ...$set->toArray())
        );

        return $self;
    }

    public function equals(Implementation $set): bool
    {
        if ($this->size() !== $set->size()) {
            return false;
        }

        return $this->intersect($set)->size() === $this->size();
    }

    public function filter(callable $predicate): self
    {
        $set = clone $this;
        $set->values = $this->values->filter($predicate);

        return $set;
    }

    public function foreach(callable $function): void
    {
        $this->values->foreach($function);
    }

    public function groupBy(callable $discriminator): Map
    {
        $map = $this->values->groupBy($discriminator);

        return $map->reduce(
            Map::of($map->keyType(), Set::class),
            function(Map $carry, $key, Sequence $values): Map {
                return $carry->put(
                    $key,
                    Set::of($this->type, ...$values->toArray()),
                );
            }
        );
    }

    public function map(callable $function): self
    {
        $function = function($value) use ($function) {
            $value = $function($value);
            ($this->validate)($value, 1);

            return $value;
        };

        return $this->reduce(
            $this->clear(),
            function(self $carry, $value) use ($function): self {
                return $carry->add($function($value));
            }
        );
    }

    public function partition(callable $predicate): Map
    {
        $partitions = $this->values->partition($predicate);

        return Map::of('bool', Set::class)
            (true, Set::of($this->type, ...$partitions->get(true)->toArray()))
            (false, Set::of($this->type, ...$partitions->get(false)->toArray()));
    }

    public function join(string $separator): Str
    {
        return $this->values->join($separator);
    }

    public function sort(callable $function): Sequence
    {
        return $this->values->sort($function);
    }

    public function merge(Implementation $set): self
    {
        return $set->reduce(
            $this,
            function(self $carry, $value): self {
                return $carry->add($value);
            }
        );
    }

    public function reduce($carry, callable $reducer)
    {
        return $this->values->reduce($carry, $reducer);
    }

    public function clear(): self
    {
        return new self($this->type);
    }

    public function empty(): bool
    {
        return $this->values->empty();
    }
}
