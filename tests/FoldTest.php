<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\Fold;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class FoldTest extends TestCase
{
    use BlackBox;

    public function testMap()
    {
        $this
            ->forAll(
                Set\Type::any(),
                Set\Type::any(),
            )
            ->then(function($source, $mapped) {
                $fold = Fold::with($source)
                    ->map(function($in) use ($source, $mapped) {
                        $this->assertSame($source, $in);

                        return $mapped;
                    })
                    ->match(
                        static fn($with) => $with,
                        static fn() => null,
                        static fn() => null,
                    );

                $this->assertSame($mapped, $fold);

                $fold = Fold::result($source)
                    ->map(static fn() => $mapped)
                    ->match(
                        static fn() => null,
                        static fn($result) => $result,
                        static fn() => null,
                    );

                $this->assertSame($source, $fold);

                $fold = Fold::fail($source)
                    ->map(static fn() => $mapped)
                    ->match(
                        static fn() => null,
                        static fn() => null,
                        static fn($failure) => $failure,
                    );

                $this->assertSame($source, $fold);
            });
    }

    public function testFlatMap()
    {
        $this
            ->forAll(
                Set\Type::any(),
                Set\Composite::immutable(
                    static fn($value, $type) => $type($value),
                    Set\Type::any(),
                    Set\Elements::of(
                        static fn($value) => Fold::with($value),
                        static fn($value) => Fold::result($value),
                        static fn($value) => Fold::fail($value),
                    ),
                ),
            )
            ->then(function($source, $mapped) {
                $fold = Fold::with($source)
                    ->flatMap(function($in) use ($source, $mapped) {
                        $this->assertSame($source, $in);

                        return $mapped;
                    });

                $this->assertSame($mapped, $fold);

                $fold = Fold::result($source)
                    ->flatMap(static fn() => $mapped)
                    ->match(
                        static fn() => null,
                        static fn($result) => $result,
                        static fn() => null,
                    );

                $this->assertSame($source, $fold);

                $fold = Fold::fail($source)
                    ->flatMap(static fn() => $mapped)
                    ->match(
                        static fn() => null,
                        static fn() => null,
                        static fn($failure) => $failure,
                    );

                $this->assertSame($source, $fold);
            });
    }

    public function testMaybe()
    {
        $this
            ->forAll(Set\Type::any())
            ->then(function($source) {
                $this->assertFalse(
                    Fold::with($source)
                        ->maybe()
                        ->match(
                            static fn() => true,
                            static fn() => false,
                        ),
                );
                $this->assertSame(
                    $source,
                    Fold::result($source)
                        ->maybe()
                        ->match(
                            static fn($either) => $either->match(
                                static fn($result) => $result,
                                static fn() => null,
                            ),
                            static fn() => null,
                        ),
                );
                $this->assertSame(
                    $source,
                    Fold::fail($source)
                        ->maybe()
                        ->match(
                            static fn($either) => $either->match(
                                static fn() => null,
                                static fn($fail) => $fail,
                            ),
                            static fn() => null,
                        ),
                );
            });
    }

    public function testMapResult()
    {
        $this
            ->forAll(
                Set\Type::any(),
                Set\Type::any(),
            )
            ->then(function($source, $mapped) {
                $fold = Fold::with($source)
                    ->mapResult(static fn() => $mapped)
                    ->match(
                        static fn($with) => $with,
                        static fn() => null,
                        static fn() => null,
                    );

                $this->assertSame($source, $fold);

                $fold = Fold::result($source)
                    ->mapResult(function($in) use ($source, $mapped) {
                        $this->assertSame($source, $in);

                        return $mapped;
                    })
                    ->match(
                        static fn() => null,
                        static fn($result) => $result,
                        static fn() => null,
                    );

                $this->assertSame($mapped, $fold);

                $fold = Fold::fail($source)
                    ->mapResult(static fn() => $mapped)
                    ->match(
                        static fn() => null,
                        static fn() => null,
                        static fn($failure) => $failure,
                    );

                $this->assertSame($source, $fold);
            });
    }

    public function testMapFailure()
    {
        $this
            ->forAll(
                Set\Type::any(),
                Set\Type::any(),
            )
            ->then(function($source, $mapped) {
                $fold = Fold::with($source)
                    ->mapFailure(static fn() => $mapped)
                    ->match(
                        static fn($with) => $with,
                        static fn() => null,
                        static fn() => null,
                    );

                $this->assertSame($source, $fold);

                $fold = Fold::result($source)
                    ->mapFailure(static fn() => $mapped)
                    ->match(
                        static fn() => null,
                        static fn($result) => $result,
                        static fn() => null,
                    );

                $this->assertSame($source, $fold);

                $fold = Fold::fail($source)
                    ->mapFailure(function($in) use ($source, $mapped) {
                        $this->assertSame($source, $in);

                        return $mapped;
                    })
                    ->match(
                        static fn() => null,
                        static fn() => null,
                        static fn($failure) => $failure,
                    );

                $this->assertSame($mapped, $fold);
            });
    }
}
