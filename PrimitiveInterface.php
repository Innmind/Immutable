<?php
declare(strict_types = 1);

namespace Innmind\Immutable;

interface PrimitiveInterface
{
    /**
     * Return the raw php value
     *
     * @return mixed
     */
    public function toPrimitive();
}
