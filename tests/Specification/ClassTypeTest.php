<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Specification;

use Innmind\Immutable\{
    Specification\ClassType,
    SpecificationInterface,
    Exception\InvalidArgumentException
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

    public function testThrowWhenValidationFails()
    {
        $this->expectException(InvalidArgumentException::class);

        (new ClassType('Foo'))->validate(new \stdClass);
    }
}
