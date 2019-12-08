<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\ValidateArgument\VariableType;
use PHPUnit\Framework\TestCase;

class VariableTypeTest extends TestCase
{
    public function testValidate()
    {
        $type = new VariableType;

        $this->assertNull($type('foo', 1));
        $this->assertNull($type(42, 1));
        $this->assertNull($type(42.1, 1));
        $this->assertNull($type(true, 1));
        $this->assertNull($type([], 1));
    }

    public function testThrowWhenValidationFails()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type variable, stdClass given');

        (new VariableType)(new \stdClass, 1);
    }
}
