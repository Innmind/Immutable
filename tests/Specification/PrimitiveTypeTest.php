<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Specification;

use Innmind\Immutable\{
    Specification\PrimitiveType,
    SpecificationInterface
};
use PHPUnit\Framework\TestCase;

class PrimitiveTypeTest extends TestCase
{
    public function testValidate()
    {
        $this->assertNull(
            (new PrimitiveType('int'))->validate(42)
        );
    }

    /**
     * @expectedException Innmind\Immutable\Exception\InvalidArgumentException
     */
    public function testThrowWhenValidationFails()
    {
        (new PrimitiveType('int'))->validate(42.0);
    }
}
