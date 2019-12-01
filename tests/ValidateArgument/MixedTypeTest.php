<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\ValidateArgument\MixedType;
use PHPUnit\Framework\TestCase;

class MixedTypeTest extends TestCase
{
    public function testValidate()
    {
        $type = new MixedType;

        $this->assertNull($type('foo', 1));
        $this->assertNull($type(42, 1));
        $this->assertNull($type(42.1, 1));
        $this->assertNull($type(true, 1));
        $this->assertNull($type([], 1));
        $this->assertNull($type(new \stdClass, 1));
    }
}
