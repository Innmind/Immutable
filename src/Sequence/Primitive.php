<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\{
    Map,
    Sequence,
    Str,
    Type,
    ValidateArgument,
    Exception\OutOfBoundException,
    Exception\LogicException,
    Exception\ElementNotFoundException,
    Exception\CannotGroupEmptyStructure,
};

final class Primitive implements Implementation
{
    private string $type;
    private ValidateArgument $validate;
    private array $values;
    private ?int $size;

    public function __construct(string $type, ...$values)
    {
        $this->type = $type;
        $this->validate = Type::of($type);
        $this->values = $values;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function size(): int
    {
        return $this->size ?? $this->size = \count($this->values);
    }

    public function count(): int
    {
        return $this->size();
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function get(int $index)
    {
        if (!$this->has($index)) {
            throw new OutOfBoundException;
        }

        return $this->values[$index];
    }

    public function diff(Implementation $sequence): self
    {
        return $this->filter(static function($value) use ($sequence): bool {
            return !$sequence->contains($value);
        });
    }

    public function distinct(): self
    {
        return $this->reduce(
            $this->clear(),
            static function(self $values, $value): self {
                if ($values->contains($value)) {
                    return $values;
                }

                return $values->add($value);
            }
        );
    }

    public function drop(int $size): self
    {
        $self = $this->clear();
        $self->values = \array_slice($this->values, $size);

        return $self;
    }

    public function dropEnd(int $size): self
    {
        $self = $this->clear();
        $self->values = \array_slice($this->values, 0, $this->size() - $size);

        return $self;
    }

    public function equals(Implementation $sequence): bool
    {
        return $this->values === $sequence->toArray();
    }

    public function filter(callable $predicate): self
    {
        $self = $this->clear();
        $self->values = \array_values(\array_filter(
            $this->values,
            $predicate
        ));

        return $self;
    }

    public function foreach(callable $function): void
    {
        foreach ($this->values as $value) {
            $function($value);
        }
    }

    public function groupBy(callable $discriminator): Map
    {
        if ($this->size() === 0) {
            throw new CannotGroupEmptyStructure;
        }

        $map = null;

        foreach ($this->values as $value) {
            $key = $discriminator($value);

            if ($map === null) {
                $map = Map::of(
                    Type::determine($key),
                    Sequence::class
                );
            }

            if ($map->contains($key)) {
                $map = $map->put(
                    $key,
                    $map->get($key)->add($value)
                );
            } else {
                $map = $map->put($key, Sequence::of($this->type, $value));
            }
        }

        return $map;
    }

    public function first()
    {
        if ($this->size() === 0) {
            throw new OutOfBoundException;
        }

        return $this->values[0];
    }

    public function last()
    {
        if ($this->size() === 0) {
            throw new OutOfBoundException;
        }

        return $this->values[$this->size() - 1];
    }

    public function contains($element): bool
    {
        return \in_array($element, $this->values, true);
    }

    public function indexOf($element): int
    {
        $index = \array_search($element, $this->values, true);

        if ($index === false) {
            throw new ElementNotFoundException;
        }

        return $index;
    }

    public function indices(): self
    {
        if ($this->size() === 0) {
            return new self('int');
        }

        return new self('int', ...\range(0, $this->size() - 1));
    }

    public function map(callable $function): self
    {
        $function = function($value) use ($function) {
            $value = $function($value);
            ($this->validate)($value, 1);

            return $value;
        };

        $self = clone $this;
        $self->values = \array_map($function, $this->values);

        return $self;
    }

    public function pad(int $size, $element): self
    {
        $self = $this->clear();
        $self->values = \array_pad($this->values, $size, $element);

        return $self;
    }

    public function partition(callable $predicate): Map
    {
        $truthy = [];
        $falsy = [];

        foreach ($this->values as $value) {
            if ($predicate($value) === true) {
                $truthy[] = $value;
            } else {
                $falsy[] = $value;
            }
        }

        $true = Sequence::of($this->type, ...$truthy);
        $false = Sequence::of($this->type, ...$falsy);

        return Map::of('bool', Sequence::class)
            (true, $true)
            (false, $false);
    }

    public function slice(int $from, int $until): self
    {
        $self = $this->clear();
        $self->values = \array_slice(
            $this->values,
            $from,
            $until - $from,
        );

        return $self;
    }

    public function splitAt(int $index): Sequence
    {
        return Sequence::of(Sequence::class)
            ->add(Sequence::of($this->type, ...$this->slice(0, $index)->toArray()))
            ->add(Sequence::of($this->type, ...$this->slice($index, $this->size())->toArray()));
    }

    public function take(int $size): self
    {
        return $this->slice(0, $size);
    }

    public function takeEnd(int $size): self
    {
        return $this->slice($this->size() - $size, $this->size());
    }

    public function append(Implementation $sequence): self
    {
        $self = $this->clear();
        $self->values = \array_merge($this->values, $sequence->toArray());

        return $self;
    }

    public function intersect(Implementation $sequence): self
    {
        return $this->filter(static function($value) use ($sequence): bool {
            return $sequence->contains($value);
        });
    }

    public function join(string $separator): Str
    {
        return Str::of(\implode($separator, $this->values));
    }

    public function add($element): self
    {
        $self = clone $this;
        $self->values[] = $element;
        $self->size = $this->size() + 1;

        return $self;
    }

    public function sort(callable $function): self
    {
        $self = clone $this;
        \usort($self->values, $function);

        return $self;
    }

    public function reduce($carry, callable $reducer)
    {
        return \array_reduce($this->values, $reducer, $carry);
    }

    public function clear(): Implementation
    {
        return new self($this->type);
    }

    public function reverse(): self
    {
        $self = clone $this;
        $self->values = \array_reverse($this->values);

        return $self;
    }

    public function empty(): bool
    {
        return !$this->has(0);
    }

    private function has(int $index): bool
    {
        return \array_key_exists($index, $this->values);
    }
}
