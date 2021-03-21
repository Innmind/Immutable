<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\Maybe;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class MaybeTest extends TestCase
{
    use BlackBox;

    public function testMapIsNotCalledWhenNoValue()
    {
        $maybe = Maybe::nothing();

        $this->assertInstanceOf(Maybe::class, $maybe->map(static function() {
            throw new \Exception;
        }));
    }

    public function testMaybeNothingMatch()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($unwanted, $nothing) {
                $this->assertSame(
                    $nothing,
                    Maybe::nothing()->match(
                        static fn() => $unwanted,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testMaybeOfNullReturnsANothingImplementation()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($unwanted, $nothing) {
                $this->assertSame(
                    $nothing,
                    Maybe::of(null)->match(
                        static fn() => $unwanted,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testMaybeJustMatch()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $just, $nothing) {
                $this->assertSame(
                    $just,
                    Maybe::just($initial)->match(
                        function($value) use ($initial, $just) {
                            $this->assertSame($initial, $value);

                            return $just;
                        },
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testMaybeOfValueReturnsAJustImplementation()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $just, $nothing) {
                $this->assertSame(
                    $just,
                    Maybe::of($initial)->match(
                        static fn() => $just,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testMapIsCalledWhenThereIsAValue()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $mapped, $unwanted) {
                $maybe = Maybe::just($initial);
                $maybe2 = $maybe->map(function($value) use ($initial, $mapped) {
                    $this->assertSame($initial, $value);

                    return $mapped;
                });

                $this->assertInstanceOf(Maybe::class, $maybe2);
                $this->assertNotSame($maybe, $maybe2);
                $this->assertSame(
                    $mapped,
                    $maybe2->match(
                        static fn($value) => $value,
                        static fn() => $unwanted,
                    ),
                );
            });
    }

    public function testFlatMapIsNotCalledWhenNoValue()
    {
        $this->assertInstanceOf(Maybe::class, Maybe::nothing()->flatMap(static function() {
            throw new \Exception;
        }));
    }

    public function testFlatMapIsCalledWhenThereIsAValue()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $mapped, $nothing) {
                $expected = Maybe::just($mapped);
                $maybe = Maybe::just($initial)->flatMap(function($value) use ($initial, $expected) {
                    $this->assertSame($initial, $value);

                    return $expected;
                });

                $this->assertSame($expected, $maybe);
                $this->assertSame(
                    $mapped,
                    $maybe->match(
                        static fn($value) => $value,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testOtherwiseIsCalledWhenNoValue()
    {
        $this
            ->forAll(Set\AnyType::any())
            ->then(function($value) {
                $expected = Maybe::of($value);

                $this->assertSame(
                    $expected,
                    Maybe::nothing()->otherwise(static fn() => $expected),
                );
            });
    }

    public function testOtherwiseIsNotCalledWhenThereIsAValue()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $nothing) {
                $maybe = Maybe::just($initial);
                $maybe2 = $maybe->otherwise(static function() {
                    throw new \Exception;
                });

                $this->assertInstanceOf(Maybe::class, $maybe2);
                $this->assertNotSame($maybe, $maybe2);
                $this->assertSame(
                    $initial,
                    $maybe2->match(
                        static fn($value) => $value,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testFilterPredicateIsNotCalledWhenNoValue()
    {
        $this->assertInstanceOf(Maybe::class, Maybe::nothing()->filter(function() {
            throw new \Exception;
        }));
    }

    public function testReturnItselfWhenFilterPredicateReturnsTrue()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $nothing) {
                $maybe = Maybe::just($initial)->filter(function($value) use ($initial) {
                    $this->assertSame($initial, $value);

                    return true;
                });

                $this->assertInstanceOf(Maybe::class, $maybe);
                $this->assertSame(
                    $initial,
                    $maybe->match(
                        static fn($value) => $value,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    public function testReturnsANothingWhenFilterPredicateReturnsFalse()
    {
        $this
            ->forAll(
                $this->value(),
                $this->value(),
            )
            ->then(function($initial, $nothing) {
                $maybe = Maybe::just($initial)->filter(function($value) use ($initial) {
                    $this->assertSame($initial, $value);

                    return false;
                });

                $this->assertInstanceOf(Maybe::class, $maybe);
                $this->assertSame(
                    $nothing,
                    $maybe->match(
                        static fn($value) => $value,
                        static fn() => $nothing,
                    ),
                );
            });
    }

    private function value(): Set
    {
        return Set\AnyType::any()->filter(static fn($value) => $value !== null);
    }
}
