<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Sequence;

use Innmind\Immutable\Exception\LogicException;

/**
 * @internal
 * @template T
 * @psalm-immutable Not really immutable but to simplify declaring immutability of other structures
 */
final class Aggregate
{
    /** @var \Iterator<T> */
    private \Iterator $values;

    /**
     * @param \Iterator<T> $values
     */
    public function __construct(\Iterator $values)
    {
        $this->values = $values;
    }

    /**
     * @template A
     *
     * @param callable(T|A, T): \Iterator<A> $map
     *
     * @return \Generator<T|A>
     */
    public function __invoke(callable $map): \Generator
    {
        // we use an object to check if the aggregate below as any value in order
        // to be sure there is no false equality (as the values may contain null)
        $void = new \stdClass;

        /** @psalm-suppress ImpureMethodCall */
        if (!$this->values->valid()) {
            return;
        }

        /** @psalm-suppress ImpureMethodCall */
        $n2 = $this->values->current();
        /** @psalm-suppress ImpureMethodCall */
        $this->values->next();

        /** @psalm-suppress ImpureMethodCall */
        if (!$this->values->valid()) {
            yield $n2;

            return;
        }

        /** @psalm-suppress ImpureMethodCall */
        $n1 = $this->values->current();

        /** @psalm-suppress ImpureMethodCall */
        while ($this->values->valid()) {
            /** @psalm-suppress ImpureFunctionCall */
            $aggregate = $this->walk($map($n2, $n1), $void);

            foreach ($aggregate as $element) {
                yield $element;
            }

            /**
             * @psalm-suppress ImpureMethodCall
             * @var T|A
             */
            $n2 = $aggregate->getReturn();

            if ($n2 === $void) {
                // enforce returning at least one element to prevent confusing
                // behavior
                // the alternative would be to pull 2 elements from the source
                // values but if $map always return an empty sequence then the
                // whole sequence will only contain the last source value which
                // can be confusing
                throw new LogicException('Aggregates must always return at least one element');
            }

            /** @psalm-suppress ImpureMethodCall */
            $this->values->next();

            // this condition is to accomodate the Accumulate iterator that will
            // always create a new element when calling current
            /** @psalm-suppress ImpureMethodCall */
            if (!$this->values->valid()) {
                break;
            }

            /** @psalm-suppress ImpureMethodCall */
            $n1 = $this->values->current();
        }

        yield $n2;
    }

    /**
     * @template W
     * @param \Iterator<W> $values
     *
     * @return \Generator<mixed, W, mixed, W|\stdClass>
     */
    private function walk(\Iterator $values, \stdClass $void): \Generator
    {
        /** @psalm-suppress ImpureMethodCall */
        if (!$values->valid()) {
            return $void;
        }

        /** @psalm-suppress ImpureMethodCall */
        $n2 = $values->current();
        /** @psalm-suppress ImpureMethodCall */
        $values->next();

        /** @psalm-suppress ImpureMethodCall */
        if (!$values->valid()) {
            return $n2;
        }

        /** @psalm-suppress ImpureMethodCall */
        $n1 = $values->current();

        /** @psalm-suppress ImpureMethodCall */
        while ($values->valid()) {
            yield $n2;
            /** @psalm-suppress ImpureMethodCall */
            $values->next();
            $n2 = $n1;

            // this condition is to accomodate the Accumulate iterator that will
            // always create a new element when calling current
            /** @psalm-suppress ImpureMethodCall */
            if (!$values->valid()) {
                break;
            }

            /** @psalm-suppress ImpureMethodCall */
            $n1 = $values->current();
        }

        return $n2;
    }
}
