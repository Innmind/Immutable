<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    Type,
    Specification\PrimitiveType,
    Specification\VariableType,
    Specification\MixedType,
    Specification\ClassType
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
