<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Specification;

use Innmind\Immutable\{
    Specification\UnionType,
    SpecificationInterface,
    Exception\InvalidArgumentException
};
use PHPUnit\Framework\TestCase;

class UnionTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            SpecificationInterface::class,
            new UnionType(
                $this->createMock(SpecificationInterface::class),
                $this->createMock(SpecificationInterface::class)
            )
        );
    }

    public function testDoesntThrowWhenAtLeastOneTypeAcceptsTheValue()
    {
        $type = new UnionType(
            $inner1 = $this->createMock(SpecificationInterface::class),
            $inner2 = $this->createMock(SpecificationInterface::class),
            $inner3 = $this->createMock(SpecificationInterface::class)
        );
        $value = new \stdClass;
        $inner1
            ->expects($this->once())
            ->method('validate')
            ->with($value)
            ->will($this->throwException(new InvalidArgumentException));
        $inner2
            ->expects($this->once())
            ->method('validate')
            ->with($value);
        $inner3
            ->expects($this->never())
            ->method('validate');

        $this->assertNull($type->validate($value));
    }

    public function testThrowWhenNoneOfTheTypesAcceptTheValue()
    {
        $type = new UnionType(
            $inner1 = $this->createMock(SpecificationInterface::class),
            $inner2 = $this->createMock(SpecificationInterface::class),
            $inner3 = $this->createMock(SpecificationInterface::class)
        );
        $value = new \stdClass;
        $inner1
            ->expects($this->once())
            ->method('validate')
            ->with($value)
            ->will($this->throwException(new InvalidArgumentException));
        $inner2
            ->expects($this->once())
            ->method('validate')
            ->with($value)
            ->will($this->throwException(new InvalidArgumentException));
        $inner3
            ->expects($this->once())
            ->method('validate')
            ->with($value)
            ->will($this->throwException(new InvalidArgumentException));

        $this->expectException(InvalidArgumentException::class);

        $type->validate($value);
    }
}
