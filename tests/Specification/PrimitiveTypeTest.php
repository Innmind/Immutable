<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Specification;

use Innmind\Immutable\{
    Specification\PrimitiveType,
    SpecificationInterface,
    Exception\InvalidArgumentException
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

    public function testThrowWhenValidationFails()
    {
        $this->expectException(InvalidArgumentException::class);

        (new PrimitiveType('int'))->validate(42.0);
    }
}
