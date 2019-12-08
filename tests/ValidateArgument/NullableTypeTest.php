<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\{
    ValidateArgument\NullableType,
    ValidateArgument,
};
use PHPUnit\Framework\TestCase;

class NullableTypeTest extends TestCase
{
    public function testInterface()
    {
        $type = new NullableType(
            $this->createMock(ValidateArgument::class)
        );

        $this->assertInstanceOf(ValidateArgument::class, $type);
    }

    public function testDoesntThrowWhenValueIsNull()
    {
        $type = new NullableType(
            $inner = $this->createMock(ValidateArgument::class)
        );
        $inner
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($type(null, 1));
    }

    public function testUseUnderlyingTypeWhenValueIsNotNull()
    {
        $type = new NullableType(
            $inner = $this->createMock(ValidateArgument::class)
        );
        $value = new \stdClass;
        $inner
            ->expects($this->once())
            ->method('__invoke')
            ->with($value, 1);

        $this->assertNull($type($value, 1));
    }
}
