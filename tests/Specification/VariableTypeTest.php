<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Specification;

use Innmind\Immutable\{
    Specification\VariableType,
    Exception\InvalidArgumentException
};
use PHPUnit\Framework\TestCase;

class VariableTypeTest extends TestCase
{
    public function testValidate()
    {
        $type = new VariableType;

        $this->assertNull($type->validate('foo'));
        $this->assertNull($type->validate(42));
        $this->assertNull($type->validate(42.1));
        $this->assertNull($type->validate(true));
        $this->assertNull($type->validate([]));
    }

    public function testThrowWhenValidationFails()
    {
        $this->expectException(InvalidArgumentException::class);

        (new VariableType)->validate(new \stdClass);
    }
}
