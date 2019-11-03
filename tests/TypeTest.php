<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Type,
    Specification\PrimitiveType,
    Specification\VariableType,
    Specification\MixedType,
    Specification\ClassType,
    Specification\NullableType
};
use PHPUnit\Framework\TestCase;

class TypeTest extends TestCase
{
    public function testGetSpecificationFor()
    {
        $this->assertInstanceOf(PrimitiveType::class, Type::of('null'));
        $this->assertInstanceOf(PrimitiveType::class, Type::of('string'));
        $this->assertInstanceOf(PrimitiveType::class, Type::of('int'));
        $this->assertInstanceOf(PrimitiveType::class, Type::of('float'));
        $this->assertInstanceOf(PrimitiveType::class, Type::of('array'));
        $this->assertInstanceOf(PrimitiveType::class, Type::of('bool'));
        $this->assertInstanceOf(PrimitiveType::class, Type::of('object'));
        $this->assertInstanceOf(VariableType::class, Type::of('variable'));
        $this->assertInstanceOf(MixedType::class, Type::of('mixed'));
        $this->assertInstanceOf(ClassType::class, Type::of('stdClass'));
        $this->assertInstanceOf(NullableType::class, Type::of('?string'));
        $this->assertInstanceOf(NullableType::class, Type::of('?int'));
        $this->assertInstanceOf(NullableType::class, Type::of('?float'));
        $this->assertInstanceOf(NullableType::class, Type::of('?array'));
        $this->assertInstanceOf(NullableType::class, Type::of('?bool'));
        $this->assertInstanceOf(NullableType::class, Type::of('?object'));
        $this->assertInstanceOf(NullableType::class, Type::of('?variable'));
        $this->assertInstanceOf(NullableType::class, Type::of('?stdClass'));

        $this->assertNull(Type::of('?string')->validate('foo'));
        $this->assertNull(Type::of('?int')->validate(42));
        $this->assertNull(Type::of('?float')->validate(2.4));
        $this->assertNull(Type::of('?array')->validate([]));
        $this->assertNull(Type::of('?bool')->validate(true));
        $this->assertNull(Type::of('?object')->validate(new \stdClass));
        $this->assertNull(Type::of('?variable')->validate(false));
        $this->assertNull(Type::of('?stdClass')->validate(new \stdClass));
    }

    public function testTypeOfNullableNullIsNotAccepted()
    {
        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('\'null\' type is already nullable');

        Type::of('?null');
    }

    public function testTypeOfNullableMixedIsNotAccepted()
    {
        $this->expectException(\ParseError::class);
        $this->expectExceptionMessage('\'mixed\' type already accepts \'null\' values');

        Type::of('?mixed');
    }

    public function testDetermineType()
    {
        $this->assertSame('stdClass', Type::determine(new \stdClass));
        $this->assertSame('null', Type::determine(null));
        $this->assertSame('string', Type::determine(''));
        $this->assertSame('int', Type::determine(1));
        $this->assertSame('float', Type::determine(1.1));
        $this->assertSame('array', Type::determine([]));
        $this->assertSame('bool', Type::determine(true));
    }
}
