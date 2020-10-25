<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable\Exception;

use Innmind\Immutable\Exception\ElementNotFound;
use PHPUnit\Framework\TestCase;

class ElementNotFoundTest extends TestCase
{
    public function testMessage()
    {
        $this->assertSame('42', (new ElementNotFound(42))->getMessage());
        $this->assertSame('4.2', (new ElementNotFound(4.2))->getMessage());
        $this->assertSame('foo', (new ElementNotFound('foo'))->getMessage());
        $this->assertSame('null', (new ElementNotFound(null))->getMessage());
        $this->assertSame('array', (new ElementNotFound([1, 2]))->getMessage());
        $this->assertSame('true', (new ElementNotFound(true))->getMessage());
        $this->assertSame('false', (new ElementNotFound(false))->getMessage());
        $this->assertMatchesRegularExpression(
            '~^object\(stdClass\)\#\d+$~',
            (new ElementNotFound(new \stdClass))->getMessage(),
        );
    }
}
