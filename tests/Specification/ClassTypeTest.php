<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Specification;

use Innmind\Immutable\{
    Specification\ClassType,
    SpecificationInterface
};
use PHPUnit\Framework\TestCase;

class ClassTypeTest extends TestCase
{
    public function testValidate()
    {
        $this->assertNull(
            (new ClassType('stdClass'))->validate(new \stdClass)
        );
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenValidationFails()
    {
        (new ClassType('Foo'))->validate(new \stdClass);
    }
}
