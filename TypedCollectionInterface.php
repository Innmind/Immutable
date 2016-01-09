<?php

namespace Innmind\Immutable;

interface TypedCollectionInterface extends CollectionInterface
{
    /**
     * Return the type of the collection
     *
     * It usually will be a class name
     *
     * @return string
     */
    public function getType();
}
