<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Specification;

use Innmind\Immutable\Specification\MixedType;
use PHPUnit\Framework\TestCase;

class MixedTypeTest extends TestCase
{
    public function testValidate()
    {
        $type = new MixedType;

        $this->assertNull($type->validate('foo'));
        $this->assertNull($type->validate(42));
        $this->assertNull($type->validate(42.1));
        $this->assertNull($type->validate(true));
        $this->assertNull($type->validate([]));
        $this->assertNull($type->validate(new \stdClass));
    }
}
