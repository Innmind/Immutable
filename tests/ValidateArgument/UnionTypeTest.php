<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\{
    ValidateArgument\UnionType,
    ValidateArgument,
};
use PHPUnit\Framework\TestCase;

class UnionTypeTest extends TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            ValidateArgument::class,
            new UnionType(
                'foo',
                $this->createMock(ValidateArgument::class),
                $this->createMock(ValidateArgument::class)
            )
        );
    }

    public function testDoesntThrowWhenAtLeastOneTypeAcceptsTheValue()
    {
        $type = new UnionType(
            'foo',
            $inner1 = $this->createMock(ValidateArgument::class),
            $inner2 = $this->createMock(ValidateArgument::class),
            $inner3 = $this->createMock(ValidateArgument::class)
        );
        $value = new \stdClass;
        $inner1
            ->expects($this->once())
            ->method('__invoke')
            ->with($value, 1)
            ->will($this->throwException(new \TypeError));
        $inner2
            ->expects($this->once())
            ->method('__invoke')
            ->with($value, 1);
        $inner3
            ->expects($this->never())
            ->method('__invoke');

        $this->assertNull($type($value, 1));
    }

    public function testThrowWhenNoneOfTheTypesAcceptTheValue()
    {
        $type = new UnionType(
            'foo',
            $inner1 = $this->createMock(ValidateArgument::class),
            $inner2 = $this->createMock(ValidateArgument::class),
            $inner3 = $this->createMock(ValidateArgument::class)
        );
        $value = new \stdClass;
        $inner1
            ->expects($this->once())
            ->method('__invoke')
            ->with($value, 1)
            ->will($this->throwException(new \TypeError));
        $inner2
            ->expects($this->once())
            ->method('__invoke')
            ->with($value, 1)
            ->will($this->throwException(new \TypeError));
        $inner3
            ->expects($this->once())
            ->method('__invoke')
            ->with($value, 1)
            ->will($this->throwException(new \TypeError));

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type foo, stdClass given');

        $type($value, 1);
    }
}
