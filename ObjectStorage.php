<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

use Innmind\Immutable\Exception\LogicException;

/**
 * @deprecated To be removed in 2.0
 */
class ObjectStorage implements PrimitiveInterface, \Countable, \Iterator, \ArrayAccess, \Serializable
{
    private $objects;

    public function __construct(\SplObjectStorage $objects = null)
    {
        $this->objects = $objects ? clone $objects : new \SplObjectStorage;
        $this->objects->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function toPrimitive(): \SplObjectStorage
    {
        //so the inner SplObjectStorage object can't be modified
        return clone $this->objects;
    }

    /**
     * Merge storages
     *
     * @param self $storage
     *
     * @return self
     */
    public function merge(self $storage): self
    {
        $objects = new \SplObjectStorage;
        $objects->addAll($this->objects);
        $objects->addAll($storage->objects);

        return new self($objects);
    }

    /**
     * Attach a new element to the storage
     *
     * @param object $object
     * @param mixed $data
     *
     * @return self
     */
    public function attach($object, $data = null): self
    {
        $objects = clone $this->objects;
        $objects->attach($object, $data);

        return new self($objects);
    }

    /**
     * Check if the object is in this storage
     *
     * @param object $object
     *
     * @return bool
     */
    public function contains($object): bool
    {
        return $this->objects->contains($object);
    }

    /**
     * Remove the given object from the storage
     *
     * @param object $object
     *
     * @return self
     */
    public function detach($object): self
    {
        $objects = clone $this->objects;
        $objects->detach($object);

        return new self($objects);
    }

    /**
     * Get the internal has for the given object
     *
     * @param object $object
     *
     * @return string
     */
    public function getHash($object): string
    {
        return $this->objects->getHash($object);
    }

    /**
     * Return the info associated to the current object pointed by the internal pointer
     *
     * @return mixed
     */
    public function getInfo()
    {
        return $this->objects->getInfo();
    }

    /**
     * Remove all the elements contained in the given storage from the current one
     *
     * @param self $storage
     *
     * @return self
     */
    public function removeAll(self $storage): self
    {
        $objects = clone $this->objects;
        $objects->removeAll($storage->objects);

        return new self($objects);
    }

    /**
     * Remove all elements not contained in the given storage
     *
     * @param self $storage
     *
     * @return self
     */
    public function removeAllExcept(self $storage): self
    {
        $objects = clone $this->objects;
        $objects->removeAllExcept($storage->objects);

        return new self($objects);
    }

    /**
     * Associate data to the current object
     *
     * @param mixed $data
     *
     * @return self
     */
    public function setInfo($data): self
    {
        $current = $this->objects->current();
        $objects = clone $this->objects;
        $objects[$current] = $data;

        return new self($objects);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->objects->count();
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->objects->current();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->objects->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->objects->key();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->objects->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->objects->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($object): bool
    {
        return $this->objects->offsetExists($object);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($object)
    {
        return $this->objects->offsetGet($object);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($object, $data = null)
    {
        throw new LogicException(
            'You can\'t modify an immutable object storage'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($object)
    {
        throw new LogicException(
            'You can\'t modify an immutable object storage'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): string
    {
        return $this->objects->serialize();
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized): self
    {
        $objects = clone $this->objects;
        $objects->unserialize($serialized);

        return new self($objects);
    }

    /**
     * Apply the given filter on the collection
     *
     * @param callable $filterer
     *
     * @return self
     */
    public function filter(callable $filterer): self
    {
        $objects = new \SplObjectStorage;

        foreach ($this->objects as $object) {
            $data = $this->objects[$object];

            if ($filterer($object, $data) === true) {
                $objects->attach($object, $data);
            }
        }

        $this->rewind();

        return new self($objects);
    }

    /**
     * Run the given closure on each element
     *
     * @param callable $callback
     *
     * @return self
     */
    public function each(callable $callback): self
    {
        foreach ($this->objects as $object) {
            $callback($object, $this->objects[$object]);
        }

        $this->rewind();

        return $this;
    }

    /**
     * Generate a new storage based on the given mapper
     *
     * @param callable $mapper
     *
     * @return self
     */
    public function map(callable $mapper): self
    {
        $objects = new \SplObjectStorage;

        foreach ($this->objects as $object) {
            $objects->attach(
                $mapper($object, $this->objects[$object])
            );
        }

        $this->rewind();

        return new self($objects);
    }
}
