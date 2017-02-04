<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\InvalidArgumentException;
use Innmind\Immutable\Exception\BadMethodCallException;

/**
 * @deprecated To be removed in 2.0
 */
class TypedCollection extends Collection implements TypedCollectionInterface
{
    private $type;

    /**
     * Constructor
     *
     * @param string $type The class every element must respect
     * @param array $values
     */
    public function __construct($type, array $values)
    {
        $type = (string) $type;
        $this->validate($type, $values);

        $this->type = $type;
        parent::__construct($values);
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
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
    public function filter(callable $filter = null): CollectionInterface
    {
        return new self(
            $this->type,
            parent::filter($filter)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function intersect(CollectionInterface $collection): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::intersect($collection)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function chunk(int $size): CollectionInterface
    {
        $chunks = parent::chunk($size);
        $subs = [];

        foreach ($chunks as $chunk) {
            $subs[] = new self(
                $this->type,
                $chunk->toPrimitive()
            );
        }

        return new parent($subs);
    }

    /**
     * {@inheritdoc}
     */
    public function shift(): CollectionInterface
    {
        return new self(
            $this->type,
            parent::shift()->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function uintersect(CollectionInterface $collection, callable $intersecter): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::uintersect($collection, $intersecter)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function keyIntersect(CollectionInterface $collection): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::keyIntersect($collection)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $mapper): CollectionInterface
    {
        return new self(
            $this->type,
            parent::map($mapper)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function pad(int $size, $value): CollectionInterface
    {
        $this->validate($this->type, [$value]);

        return new self(
            $this->type,
            parent::pad($size, $value)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function pop(): CollectionInterface
    {
        return new self(
            $this->type,
            parent::pop()->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function diff(CollectionInterface $collection): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::diff($collection)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function push($value): CollectionInterface
    {
        $this->validate($this->type, [$value]);

        return new self(
            $this->type,
            parent::push($value)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function rand(int $num = 1): CollectionInterface
    {
        return new self(
            $this->type,
            parent::rand($num)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function merge(CollectionInterface $collection): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::merge($collection)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function slice(int $offset, int $length = null, bool $preserveKeys = false): CollectionInterface
    {
        return new self(
            $this->type,
            parent::slice($offset, $length, $preserveKeys)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function udiff(CollectionInterface $collection, callable $differ): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::udiff($collection, $differ)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function splice(int $offset, int $length = 0, $replacement = []): CollectionInterface
    {
        return new self(
            $this->type,
            parent::splice($offset, $length, $replacement)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unique(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        return new self(
            $this->type,
            parent::unique($flags)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function values(): CollectionInterface
    {
        return new self(
            $this->type,
            parent::values()->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function replace(CollectionInterface $collection): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::replace($collection)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(bool $preserveKeys = false): CollectionInterface
    {
        return new self(
            $this->type,
            parent::reverse($preserveKeys)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unshift($value): CollectionInterface
    {
        $this->validate($this->type, [$value]);

        return new self(
            $this->type,
            parent::unshift($value)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function keyDiff(CollectionInterface $collection): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::keyDiff($collection)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function ukeyDiff(CollectionInterface $collection, callable $differ): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::ukeyDiff($collection, $differ)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function associativeDiff(CollectionInterface $collection): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::associativeDiff($collection)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function ukeyIntersect(CollectionInterface $collection, callable $intersecter): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::ukeyIntersect($collection, $intersecter)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function associativeIntersect(CollectionInterface $collection): CollectionInterface
    {
        $this->validateCollection($collection);

        return new self(
            $this->type,
            parent::associativeIntersect($collection)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function sort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        return new self(
            $this->type,
            parent::sort($flags)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function associativeSort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        return new self(
            $this->type,
            parent::associativeSort($flags)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function keySort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        return new self(
            $this->type,
            parent::keySort($flags)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function ukeySort(callable $sorter): CollectionInterface
    {
        return new self(
            $this->type,
            parent::ukeySort($sorter)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function reverseSort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        return new self(
            $this->type,
            parent::reverseSort($flags)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function usort(callable $sorter): CollectionInterface
    {
        return new self(
            $this->type,
            parent::usort($sorter)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function associativeReverseSort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        return new self(
            $this->type,
            parent::associativeReverseSort($flags)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function keyReverseSort(int $flags = self::SORT_REGULAR): CollectionInterface
    {
        return new self(
            $this->type,
            parent::keyReverseSort($flags)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function uassociativeSort(callable $sorter): CollectionInterface
    {
        return new self(
            $this->type,
            parent::uassociativeSort($sorter)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function naturalSort(): CollectionInterface
    {
        return new self(
            $this->type,
            parent::naturalSort()->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function shuffle(): CollectionInterface
    {
        return new self(
            $this->type,
            parent::shuffle()->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function take(int $size, bool $preserveKeys = false): CollectionInterface
    {
        return new self(
            $this->type,
            parent::take($size, $preserveKeys)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function grep(string $pattern, bool $revert = false): CollectionInterface
    {
        return new self(
            $this->type,
            parent::grep($pattern, $revert)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value): CollectionInterface
    {
        $this->validate($this->type, [$value]);

        return new self(
            $this->type,
            parent::set($key, $value)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function contains($value): bool
    {
        //avoid searching the collection if we know it's not of the same type
        try {
            $this->validate($this->type, [$value]);
        } catch (InvalidArgumentException $e) {
            return false;
        }

        return parent::contains($value);
    }

    /**
     * {@inheritdoc}
     */
    public function walk(callable $walker): CollectionInterface
    {
        return new self(
            $this->type,
            parent::walk($walker)->toPrimitive()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unset($index): CollectionInterface
    {
        return new self(
            $this->type,
            parent::unset($index)->toPrimitive()
        );
    }

    /**
     * Check if every element respect the given type
     *
     * @throws InvalidArgumentException If a value doesn't respect the type
     *
     * @param string $type
     * @param array $values
     *
     * @return void
     */
    protected function validate(string $type, array $values)
    {
        foreach ($values as $value) {
            if (!$value instanceof $type) {
                throw new InvalidArgumentException(sprintf(
                    'Each value must be an instance of "%s"',
                    $type
                ));
            }
        }
    }

    /**
     * Check if the given collection is compatible with the current one
     *
     * @throws BadMethodCallException If the collection is not compatible
     *
     * @param CollectionInterface $collection
     *
     * @return void
     */
    private function validateCollection(CollectionInterface $collection)
    {
        if (
            !$collection instanceof self ||
            $collection->getType() !== $this->type
        ) {
            throw new BadMethodCallException(
                'The given collection is not compatible'
            );
        }
    }
}
