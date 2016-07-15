<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

/**
 * @deprecated To be removed in 2.0
 */
interface TypedCollectionInterface extends CollectionInterface
{
    /**
     * Return the type of the collection
     *
     * It usually will be a class name
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Same as getType but without the get
     *
     * @return string
     */
    public function type(): string;
}
