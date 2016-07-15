<?php
declare(strict_types = 1);

namespace Innmind\Immutable\Tests\Specification;

use Innmind\Immutable\Specification\VariableType;

class VariableTypeTest extends \PHPUnit_Framework_TestCase
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

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenValidationFails()
    {
        (new VariableType)->validate(new \stdClass);
    }
}
