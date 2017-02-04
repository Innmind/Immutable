<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Specification;

use Innmind\Immutable\{
    Specification\PrimitiveType,
    SpecificationInterface
};

class PrimitiveTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testValidate()
    {
        (new PrimitiveType('int'))->validate(42);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenValidationFails()
    {
        (new PrimitiveType('int'))->validate(42.0);
    }
}
