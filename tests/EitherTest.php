<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\Either;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class EitherTest extends TestCase
{
    use BlackBox;

    public function testMatchLeft()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                Set\AnyType::any(),
            )
            ->then(function($left, $right) {
                $either = Either::left($left);

                $this->assertSame(
                    $left,
                    $either->match(
                        static fn($value) => $right,
                        function($value) use ($left) {
                            $this->assertSame($left, $value);

                            return $value;
                        },
                    ),
                );
            });
    }

    public function testMatchRight()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                Set\AnyType::any(),
            )
            ->then(function($left, $right) {
                $either = Either::right($right);

                $this->assertSame(
                    $right,
                    $either->match(
                        function($value) use ($right) {
                            $this->assertSame($right, $value);

                            return $value;
                        },
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testLeftValueIsNotMapped()
    {
        $this
            ->forAll(Set\AnyType::any())
            ->then(function($initial) {
                $either = Either::left($initial)->map(static function() {
                    throw new \Exception;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $initial,
                    $either->match(
                        static fn($value) => $value,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testRightValueIsMapped()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                Set\AnyType::any(),
            )
            ->then(function($initial, $mapped) {
                $either = Either::right($initial)->map(function($value) use ($initial, $mapped) {
                    $this->assertSame($initial, $value);

                    return $mapped;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $mapped,
                    $either->match(
                        static fn($value) => $value,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testLeftValueIsNotFlatMapped()
    {
        $this
            ->forAll(Set\AnyType::any())
            ->then(function($left) {
                $either = Either::left($left)->flatMap(static function() {
                    throw new \Exception;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $left,
                    $either->match(
                        static fn($value) => $value,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testRightValueIsFlatMapped()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                new Set\Either(
                    Set\Decorate::immutable(
                        static fn($value) => Either::left($value),
                        Set\AnyType::any(),
                    ),
                    Set\Decorate::immutable(
                        static fn($value) => Either::right($value),
                        Set\AnyType::any(),
                    ),
                ),
            )
            ->then(function($right, $expected) {
                $either = Either::right($right)->flatMap(function($value) use ($right, $expected) {
                    $this->assertSame($right, $value);

                    return $expected;
                });

                $this->assertSame($expected, $either);
            });
    }

    public function testOtherwiseIsCalledWhenLeftValue()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                new Set\Either(
                    Set\Decorate::immutable(
                        static fn($value) => Either::left($value),
                        Set\AnyType::any(),
                    ),
                    Set\Decorate::immutable(
                        static fn($value) => Either::right($value),
                        Set\AnyType::any(),
                    ),
                ),
            )
            ->then(function($left, $expected) {
                $either = Either::left($left)->otherwise(static fn() => $expected);

                $this->assertSame($expected, $either);
            });
    }

    public function testOtherwiseIsNotCalledWhenRightValue()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                Set\AnyType::any(),
            )
            ->then(function($right, $left) {
                $either = Either::right($right)->otherwise(static function() {
                    throw new \Exception;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $right,
                    $either->match(
                        static fn($value) => $value,
                        static fn() => $left,
                    ),
                );
            });
    }

    public function testKeepSameValueWhenFilteringLeftValue()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                Set\AnyType::any(),
                Set\Elements::of(true, false),
                Set\AnyType::any(),
            )
            ->then(function($left, $right, $predicate, $otherwise) {
                $either = Either::left($left)->filter(
                    static fn() => $predicate,
                    static fn() => $otherwise,
                );

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $left,
                    $either->match(
                        static fn() => $right,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testKeepSameValueWhenRightValueMatchPredicate()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                Set\AnyType::any(),
                Set\AnyType::any(),
            )
            ->then(function($right, $left, $otherwise) {
                $either = Either::right($right)->filter(
                    function($value) use ($right) {
                        $this->assertSame($right, $value);

                        return true;
                    },
                    static fn() => $otherwise,
                );

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $right,
                    $either->match(
                        static fn($value) => $value,
                        static fn() => $left,
                    ),
                );
            });
    }

    public function testUseOtherValueWhenRightValueDoesntMatchPredicate()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                Set\AnyType::any(),
                Set\AnyType::any(),
            )
            ->then(function($right, $unwanted, $otherwise) {
                $either = Either::right($right)->filter(
                    function($value) use ($right) {
                        $this->assertSame($right, $value);

                        return false;
                    },
                    static fn() => $otherwise,
                );

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $otherwise,
                    $either->match(
                        static fn($value) => $unwanted,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testRightValueIsNotLeftMapped()
    {
        $this
            ->forAll(Set\AnyType::any())
            ->then(function($initial) {
                $either = Either::right($initial)->leftMap(static function() {
                    throw new \Exception;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $initial,
                    $either->match(
                        static fn($value) => $value,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testLeftValueIsLeftMapped()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                Set\AnyType::any(),
            )
            ->then(function($initial, $mapped) {
                $either = Either::left($initial)->leftMap(function($value) use ($initial, $mapped) {
                    $this->assertSame($initial, $value);

                    return $mapped;
                });

                $this->assertInstanceOf(Either::class, $either);
                $this->assertSame(
                    $mapped,
                    $either->match(
                        static fn($value) => $value,
                        static fn($value) => $value,
                    ),
                );
            });
    }

    public function testMaybe()
    {
        $this
            ->forAll(
                Set\AnyType::any(),
                Set\AnyType::any(),
            )
            ->then(function($left, $right) {
                $this->assertSame(
                    $right,
                    Either::right($right)->maybe()->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
                );
                $this->assertNull(
                    Either::left($left)->maybe()->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
                );
            });
    }
}
