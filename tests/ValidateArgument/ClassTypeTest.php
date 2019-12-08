<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\ValidateArgument\ClassType;
use PHPUnit\Framework\TestCase;

class ClassTypeTest extends TestCase
{
    public function testValidate()
    {
        $this->assertNull(
            (new ClassType('stdClass'))(new \stdClass, 1)
        );
    }

    public function testThrowWhenValidationFails()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Foo, stdClass given');

        (new ClassType('Foo'))(new \stdClass, 1);
    }
}
