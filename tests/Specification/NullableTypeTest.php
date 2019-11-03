<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Specification;

use Innmind\Immutable\{
    Specification\NullableType,
    SpecificationInterface
};
use PHPUnit\Framework\TestCase;

class NullableTypeTest extends TestCase
{
    public function testInterface()
    {
        $type = new NullableType(
            $this->createMock(SpecificationInterface::class)
        );

        $this->assertInstanceOf(SpecificationInterface::class, $type);
    }

    public function testDoesntThrowWhenValueIsNull()
    {
        $type = new NullableType(
            $inner = $this->createMock(SpecificationInterface::class)
        );
        $inner
            ->expects($this->never())
            ->method('validate');

        $this->assertNull($type->validate(null));
    }

    public function testUseUnderlyingTypeWhenValueIsNotNull()
    {
        $type = new NullableType(
            $inner = $this->createMock(SpecificationInterface::class)
        );
        $value = new \stdClass;
        $inner
            ->expects($this->once())
            ->method('validate')
            ->with($value);

        $this->assertNull($type->validate($value));
    }
}
