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
        $type = new class {
            use Type;

            public function test(string $type)
            {
                return $this->getSpecificationFor($type);
            }
        };

        $this->assertInstanceOf(PrimitiveType::class, $type->test('null'));
        $this->assertInstanceOf(PrimitiveType::class, $type->test('string'));
        $this->assertInstanceOf(PrimitiveType::class, $type->test('int'));
        $this->assertInstanceOf(PrimitiveType::class, $type->test('float'));
        $this->assertInstanceOf(PrimitiveType::class, $type->test('array'));
        $this->assertInstanceOf(PrimitiveType::class, $type->test('bool'));
        $this->assertInstanceOf(PrimitiveType::class, $type->test('object'));
        $this->assertInstanceOf(VariableType::class, $type->test('variable'));
        $this->assertInstanceOf(MixedType::class, $type->test('mixed'));
        $this->assertInstanceOf(ClassType::class, $type->test('stdClass'));
    }

    public function testDetermineType()
    {
        $type = new class {
            use Type;

            public function test($value)
            {
                return $this->determineType($value);
            }
        };

        $this->assertSame('stdClass', $type->test(new \stdClass));
        $this->assertSame('null', $type->test(null));
        $this->assertSame('string', $type->test(''));
        $this->assertSame('int', $type->test(1));
        $this->assertSame('float', $type->test(1.1));
        $this->assertSame('array', $type->test([]));
        $this->assertSame('bool', $type->test(true));
    }
}
