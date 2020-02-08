<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\ValidateArgument;

use Innmind\Immutable\ValidateArgument\ResourceType;
use PHPUnit\Framework\TestCase;

class ResourceTypeTest extends TestCase
{
    public function testValidate()
    {
        $resource = \tmpfile();

        $this->assertNull((new ResourceType)($resource, 1));
        \fclose($resource);
        $this->assertNull((new ResourceType)($resource, 1));
    }

    public function testThrowWhenValidationFails()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type resource, float given');

        (new ResourceType)(42.0, 1);
    }
}
