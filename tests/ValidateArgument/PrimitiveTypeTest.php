<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\ValidateArgument\PrimitiveType;
use PHPUnit\Framework\TestCase;

class PrimitiveTypeTest extends TestCase
{
    public function testValidate()
    {
        $this->assertNull(
            (new PrimitiveType('int'))(42, 1)
        );
    }

    public function testThrowWhenValidationFails()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type int, float given');

        (new PrimitiveType('int'))(42.0, 1);
    }
}
