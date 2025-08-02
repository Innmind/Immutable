<?php
declare(strict_types = 1);

namespace Tests\Innmind\Immutable;

use Innmind\Immutable\{
    State,
    State\Result,
};
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};

class StateTest extends TestCase
{
    use BlackBox;

    public function testMapDoesntChangeState(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
                Set::type(),
            )
            ->prove(function($state, $initialValue, $newValue) {
                $monad = State::of(static fn($state) => Result::of($state, $initialValue));
                $monad2 = $monad->map(function($value) use ($initialValue, $newValue) {
                    $this->assertSame($initialValue, $value);

                    return $newValue;
                });

                $this->assertInstanceOf(Result::class, $monad->run($state));
                $this->assertInstanceOf(Result::class, $monad2->run($state));
                $this->assertSame($state, $monad->run($state)->state());
                $this->assertSame($state, $monad2->run($state)->state());
                $this->assertSame($initialValue, $monad->run($state)->value());
                $this->assertSame($newValue, $monad2->run($state)->value());
            });
    }

    public function testFlatMap(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::type(),
                Set::type(),
                Set::type(),
                Set::type(),
            )
            ->prove(function($initialState, $newState, $initialValue, $newValue) {
                $monad = State::of(static fn($state) => Result::of($state, $initialValue));
                $monad2 = $monad->flatMap(function($value) use ($initialValue, $initialState, $newState, $newValue) {
                    $this->assertSame($initialValue, $value);

                    return State::of(function($state) use ($initialState, $newState, $newValue) {
                        $this->assertSame($initialState, $state);

                        return Result::of($newState, $newValue);
                    });
                });

                $this->assertInstanceOf(Result::class, $monad->run($initialState));
                $this->assertInstanceOf(Result::class, $monad2->run($initialState));
                $this->assertSame($initialState, $monad->run($initialState)->state());
                $this->assertSame($newState, $monad2->run($initialState)->state());
                $this->assertSame($initialValue, $monad->run($initialState)->value());
                $this->assertSame($newValue, $monad2->run($initialState)->value());
            });
    }
}
