<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Specification;

use Innmind\Immutable\{
    Specification\ClassType,
    SpecificationInterface
};

class ClassTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testValidate()
    {
        (new ClassType('stdClass'))->validate(new \stdClass);
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenValidationFails()
    {
        (new ClassType('Foo'))->validate(new \stdClass);
    }
}
