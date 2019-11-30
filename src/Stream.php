<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\{
    LogicException,
    GroupEmptySequenceException,
    InvalidArgumentException
};

/**
 * {@inheritdoc}
 */
final class Stream implements StreamInterface
{
    private Str $type;
    private SpecificationInterface $spec;
    private Sequence $values;

    public function __construct(string $type)
    {
        $this->type = new Str($type);
        $this->spec = Type::of($type);
        $this->values = new Sequence;
    }

    /**
     * @param T $values
     */
    public static function of(string $type, ...$values): self
    {
        $self = new self($type);
        $self->values = new Sequence(...$values);
        $self->values->foreach(static function($element) use ($self): void {
            $self->spec->validate($element);
        });

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

    public function toArray(): array
    {
        return $this->values->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function get(int $index)
    {
        return $this->values->get($index);
    }

    /**
     * {@inheritdoc}
     */
    public function diff(StreamInterface $stream): StreamInterface
    {
        $this->validate($stream);

        $newStream = clone $this;
        $newStream->values = $this->values->diff(
            new Sequence(...$stream->toArray())
        );

        return $newStream;
    }

    /**
     * {@inheritdoc}
     */
    public function distinct(): StreamInterface
    {
        $stream = clone $this;
        $stream->values = $this->values->distinct();

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function drop(int $size): StreamInterface
    {
        $stream = clone $this;
        $stream->values = $this->values->drop($size);

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function dropEnd(int $size): StreamInterface
    {
        $stream = clone $this;
        $stream->values = $this->values->dropEnd($size);

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function equals(StreamInterface $stream): bool
    {
        $this->validate($stream);

        return $this->values->equals(
            new Sequence(...$stream->toArray())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $predicate): StreamInterface
    {
        $stream = clone $this;
        $stream->values = $this->values->filter($predicate);

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): StreamInterface
    {
        $this->values->foreach($function);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function groupBy(callable $discriminator): MapInterface
    {
        if ($this->size() === 0) {
            throw new GroupEmptySequenceException;
        }

        $map = null;

        foreach ($this->values->toArray() as $value) {
            $key = $discriminator($value);

            if ($map === null) {
                $map = new Map(
                    Type::determine($key),
                    StreamInterface::class
                );
            }

            if ($map->contains($key)) {
                $map = $map->put(
                    $key,
                    $map->get($key)->add($value)
                );
            } else {
                $map = $map->put(
                    $key,
                    (new self((string) $this->type))->add($value)
                );
            }
        }

        return $map;
    }

    /**
     * {@inheritdoc}
     */
    public function first()
    {
        return $this->values->first();
    }

    /**
     * {@inheritdoc}
     */
    public function last()
    {
        return $this->values->last();
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
    public function indexOf($element): int
    {
        return $this->values->indexOf($element);
    }

    /**
     * {@inheritdoc}
     */
    public function indices(): StreamInterface
    {
        return $this->values->indices();
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $function): StreamInterface
    {
        $self = clone $this;
        $self->values = $this->values->map($function);
        $self->values->foreach(function($element): void {
            $this->spec->validate($element);
        });

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function pad(int $size, $element): StreamInterface
    {
        $this->spec->validate($element);

        $stream = clone $this;
        $stream->values = $this->values->pad($size, $element);

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function partition(callable $predicate): MapInterface
    {
        $truthy = [];
        $falsy = [];

        foreach ($this->values->toArray() as $value) {
            if ($predicate($value) === true) {
                $truthy[] = $value;
            } else {
                $falsy[] = $value;
            }
        }

        $true = $this->clear();
        $true->values = new Sequence(...$truthy);
        $false = $this->clear();
        $false->values = new Sequence(...$falsy);

        return Map::of('bool', StreamInterface::class)
            (true, $true)
            (false, $false);
    }

    /**
     * {@inheritdoc}
     */
    public function slice(int $from, int $until): StreamInterface
    {
        $stream = clone $this;
        $stream->values = $this->values->slice($from, $until);

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function splitAt(int $position): StreamInterface
    {
        $stream = new self(StreamInterface::class);
        $splitted = $this->values->splitAt($position);
        $first = new self((string) $this->type);
        $second = new self((string) $this->type);
        $first->values = $splitted->first();
        $second->values = $splitted->last();

        return $stream->add($first)->add($second);
    }

    /**
     * {@inheritdoc}
     */
    public function take(int $size): StreamInterface
    {
        $stream = clone $this;
        $stream->values = $this->values->take($size);

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function takeEnd(int $size): StreamInterface
    {
        $stream = clone $this;
        $stream->values = $this->values->takeEnd($size);

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function append(StreamInterface $stream): StreamInterface
    {
        $this->validate($stream);

        $self = clone $this;
        $self->values = $this->values->append(
            new Sequence(...$stream->toArray())
        );

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function intersect(StreamInterface $stream): StreamInterface
    {
        $this->validate($stream);

        $self = clone $this;
        $self->values = $this->values->intersect(
            new Sequence(...$stream->toArray())
        );

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function join(string $separator): Str
    {
        return new Str((string) $this->values->join($separator));
    }

    /**
     * {@inheritdoc}
     */
    public function add($element): StreamInterface
    {
        $this->spec->validate($element);

        $stream = clone $this;
        $stream->values = $this->values->add($element);

        return $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function sort(callable $function): StreamInterface
    {
        $stream = clone $this;
        $stream->values = $this->values->sort($function);

        return $stream;
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
    public function clear(): StreamInterface
    {
        $self = clone $this;
        $self->values = new Sequence;

        return $self;
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(): StreamInterface
    {
        $self = clone $this;
        $self->values = $this->values->reverse();

        return $self;
    }

    public function empty(): bool
    {
        return $this->values->empty();
    }

    /**
     * Make sure the stream is compatible with the current one
     *
     * @param StreamInterface $stream
     *
     * @throws InvalidArgumentException
     *
     * @return void
     */
    private function validate(StreamInterface $stream)
    {
        if (!$stream->type()->equals($this->type)) {
            throw new InvalidArgumentException(
                'The 2 streams does not reference the same type'
            );
        }
    }
}
