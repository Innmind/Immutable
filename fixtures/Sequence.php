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
    private $predicate;

    public function __construct(string $type, Set $set, Set\Integers $sizes = null)
    {
        $this->type = $type;
        $this->set = $set;
        $this->sizes = ($sizes ?? Set\Integers::between(0, 100))->take(100);
        $this->predicate = static function(): bool {
            return true;
        };
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
            $sequence = Structure::of($this->type);
            $values = $this->set->take($size)->values();

            while ($sequence->size() < $size) {
                $sequence = ($sequence)($values->current());
                $values->next();
            }

            if (!($this->predicate)($sequence)) {
                continue;
            }

            yield $sequence;
        }
    }
}
