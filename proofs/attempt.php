<?php
declare(strict_types = 1);

use Innmind\Immutable\Attempt;
use Innmind\BlackBox\Set;

return static function($prove) {
    $exceptions = Set::of(
        new RuntimeException,
        new LogicException,
        new Exception,
        new TypeError,
        new Error,
    );

    yield $prove
        ->proof('Attempt::of() catches exceptions')
        ->given($exceptions)
        ->test(static function($assert, $e) {
            $attempt = Attempt::of(static fn() => throw $e);

            $assert->same(
                $e,
                $attempt->match(
                    static fn() => null,
                    static fn($e) => $e,
                ),
            );
        });

    yield $prove
        ->proof('Attempt::map()')
        ->given(
            Set::type(),
            Set::type(),
            $exceptions,
        )
        ->test(static function($assert, $start, $end, $e) {
            $attempt = Attempt::result($start)
                ->map(static function($value) use ($assert, $start, $end) {
                    $assert->same($start, $value);

                    return $end;
                });

            $assert->same(
                $end,
                $attempt->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );

            $attempt = Attempt::error($e)
                ->map(static fn() => $end);

            $assert->same(
                $e,
                $attempt->match(
                    static fn() => null,
                    static fn($value) => $value,
                ),
            );
        });

    yield $prove
        ->proof('Attempt::flatMap()')
        ->given(
            Set::type(),
            Set::type(),
            $exceptions,
        )
        ->test(static function($assert, $start, $end, $e) {
            $attempt = Attempt::result($start)
                ->flatMap(static function($value) use ($assert, $start, $end) {
                    $assert->same($start, $value);

                    return Attempt::result($end);
                });

            $assert->same(
                $end,
                $attempt->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );

            $attempt = Attempt::result($start)
                ->flatMap(static function($value) use ($assert, $start, $e) {
                    $assert->same($start, $value);

                    return Attempt::error($e);
                });

            $assert->same(
                $e,
                $attempt->match(
                    static fn() => null,
                    static fn($value) => $value,
                ),
            );

            $attempt = Attempt::error($e)
                ->flatMap(static fn() => Attempt::result($end));

            $assert->same(
                $e,
                $attempt->match(
                    static fn() => null,
                    static fn($value) => $value,
                ),
            );
        });

    yield $prove
        ->proof('Attempt::mapError()')
        ->given(
            $exceptions,
            $exceptions,
            Set::type(),
        )
        ->test(static function($assert, $start, $end, $value) {
            $attempt = Attempt::error($start)
                ->mapError(static function($e) use ($assert, $start, $end) {
                    $assert->same($start, $e);

                    return $end;
                });

            $assert->same(
                $end,
                $attempt->match(
                    static fn() => null,
                    static fn($value) => $value,
                ),
            );

            $attempt = Attempt::result($value)
                ->mapError(static fn() => $end);

            $assert->same(
                $value,
                $attempt->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
        });

    yield $prove
        ->proof('Attempt::mapErrorCase()')
        ->given(
            $exceptions,
            $exceptions,
            $exceptions,
        )
        ->filter(static fn($a, $b, $c) => !($a instanceof $c))
        ->test(static function($assert, $start, $end, $other) {
            $assert->same(
                $end,
                Attempt::error($start)
                    ->mapErrorCase($start::class, static fn() => $end)
                    ->match(
                        static fn() => null,
                        static fn($e) => $e,
                    ),
            );
            $assert->same(
                $start,
                Attempt::error($start)
                    ->mapErrorCase($other::class, static fn() => $end)
                    ->match(
                        static fn() => null,
                        static fn($e) => $e,
                    ),
            );
        });

    yield $prove
        ->proof('Attempt::recover()')
        ->given(
            $exceptions,
            $exceptions,
            Set::type(),
        )
        ->test(static function($assert, $start, $end, $value) {
            $attempt = Attempt::error($start)
                ->recover(static function($e) use ($assert, $start, $end) {
                    $assert->same($start, $e);

                    return Attempt::error($end);
                });

            $assert->same(
                $end,
                $attempt->match(
                    static fn() => null,
                    static fn($value) => $value,
                ),
            );

            $attempt = Attempt::error($start)
                ->recover(static function($e) use ($assert, $start, $value) {
                    $assert->same($start, $e);

                    return Attempt::result($value);
                });

            $assert->same(
                $value,
                $attempt->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );

            $attempt = Attempt::result($value)
                ->recover(static fn() => Attempt::error($end));

            $assert->same(
                $value,
                $attempt->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
        });

    yield $prove
        ->proof('Attempt::recoverCase()')
        ->given(
            $exceptions,
            $exceptions,
            $exceptions,
            Set::type(),
        )
        ->filter(static fn($a, $b, $c) => !($a instanceof $c))
        ->test(static function($assert, $start, $end, $other, $value) {
            $assert->same(
                $value,
                Attempt::error($start)
                    ->recoverCase($start::class, static fn() => Attempt::result($value))
                    ->unwrap(),
            );
            $assert->same(
                $start,
                Attempt::error($start)
                    ->recoverCase($other::class, static fn() => Attempt::result($value))
                    ->match(
                        static fn() => null,
                        static fn($e) => $e,
                    ),
            );
        });

    yield $prove
        ->proof('Attempt::maybe()')
        ->given(
            Set::type(),
            $exceptions,
        )
        ->test(static function($assert, $result, $e) {
            $assert->same(
                $result,
                Attempt::result($result)
                    ->maybe()
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->true(
                Attempt::error($e)
                    ->maybe()
                    ->match(
                        static fn() => false,
                        static fn() => true,
                    ),
            );
        });

    yield $prove
        ->proof('Attempt::either()')
        ->given(
            Set::type(),
            $exceptions,
        )
        ->test(static function($assert, $result, $e) {
            $assert->same(
                $result,
                Attempt::result($result)
                    ->either()
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->same(
                $e,
                Attempt::error($e)
                    ->either()
                    ->match(
                        static fn() => null,
                        static fn($value) => $value,
                    ),
            );
        });

    yield $prove
        ->proof('Attempt::memoize()')
        ->given(
            Set::type(),
            $exceptions,
        )
        ->test(static function($assert, $result, $e) {
            $assert->same(
                $result,
                Attempt::result($result)
                    ->memoize()
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->same(
                $e,
                Attempt::error($e)
                    ->memoize()
                    ->match(
                        static fn() => null,
                        static fn($value) => $value,
                    ),
            );

            $called = 0;
            $attempt = Attempt::defer(static function() use ($result, &$called) {
                ++$called;

                return Attempt::result($result);
            });

            $assert->same(0, $called);
            $assert->same(
                $result,
                $attempt
                    ->memoize()
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $_ = $attempt->memoize();
            $assert->same(1, $called);

            $called = 0;
            $attempt = Attempt::defer(static function() use ($e, &$called) {
                ++$called;

                return Attempt::error($e);
            });

            $assert->same(0, $called);
            $assert->same(
                $e,
                $attempt
                    ->memoize()
                    ->match(
                        static fn() => null,
                        static fn($value) => $value,
                    ),
            );
            $_ = $attempt->memoize();
            $assert->same(1, $called);
        });

    yield $prove
        ->proof('Attempt::defer()')
        ->given(
            Set::type(),
            Set::type(),
            $exceptions,
            $exceptions,
        )
        ->test(static function($assert, $result1, $result2, $e1, $e2) {
            $loaded = false;
            $attempt = Attempt::defer(static function() use ($result1, &$loaded) {
                $loaded = true;

                return Attempt::result($result1);
            })
                ->map(static fn() => $result2)
                ->flatMap(static fn() => Attempt::error($e1))
                ->recover(static fn() => Attempt::error($e2));

            $assert->false($loaded);
            $_ = $attempt->maybe();
            $assert->false($loaded);
            $_ = $attempt->either();
            $assert->false($loaded);

            $_ = $attempt->memoize();
            $assert->true($loaded);

            $assert->same(
                $e2,
                $attempt->match(
                    static fn() => null,
                    static fn($value) => $value,
                ),
            );
            $assert->false(
                $attempt->maybe()->match(
                    static fn() => true,
                    static fn() => false,
                ),
            );
            $assert->same(
                $e2,
                $attempt->either()->match(
                    static fn() => null,
                    static fn($value) => $value,
                ),
            );
        });

    yield $prove
        ->proof('Attempt::unwrap()')
        ->given(
            Set::type(),
            $exceptions,
        )
        ->test(static function($assert, $result, $e) {
            $assert->same(
                $result,
                Attempt::result($result)->unwrap(),
            );

            $assert->throws(
                static fn() => Attempt::error($e)->unwrap(),
                $e::class,
            );
        });

    yield $prove
        ->proof('Attempt::eitherWay()')
        ->given(
            Set::type(),
            Set::type(),
            $exceptions,
            $exceptions,
        )
        ->test(static function($assert, $result1, $result2, $error1, $error2) {
            $assert->same(
                $result1,
                Attempt::result($result1)
                    ->eitherWay(
                        Attempt::result(...),
                        Attempt::result(...),
                    )
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->same(
                $result2,
                Attempt::result($result1)
                    ->eitherWay(
                        static fn() => Attempt::result($result2),
                        Attempt::result(...),
                    )
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->same(
                $error1,
                Attempt::error($error1)
                    ->eitherWay(
                        Attempt::result(...),
                        Attempt::error(...),
                    )
                    ->match(
                        static fn() => null,
                        static fn($value) => $value,
                    ),
            );
            $assert->same(
                $error1,
                Attempt::error($error1)
                    ->eitherWay(
                        Attempt::error(...),
                        Attempt::result(...),
                    )
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );
            $assert->same(
                $error2,
                Attempt::error($error1)
                    ->eitherWay(
                        Attempt::result(...),
                        static fn() => Attempt::error($error2),
                    )
                    ->match(
                        static fn() => null,
                        static fn($value) => $value,
                    ),
            );
        });

    yield $prove
        ->proof('Attempt::defer()->eitherWay()')
        ->given(
            Set::type(),
            Set::type(),
            $exceptions,
            $exceptions,
        )
        ->test(static function($assert, $result1, $result2, $error1, $error2) {
            $loaded = false;
            $attempt = Attempt::defer(static function() use (&$loaded, $result1) {
                $loaded = true;

                return Attempt::result($result1);
            })->eitherWay(
                Attempt::result(...),
                Attempt::result(...),
            );
            $assert->false($loaded);
            $assert->same(
                $result1,
                $attempt->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );

            $loaded = false;
            $attempt = Attempt::defer(static function() use (&$loaded, $result1) {
                $loaded = true;

                return Attempt::result($result1);
            })->eitherWay(
                static fn() => Attempt::result($result2),
                Attempt::result(...),
            );
            $assert->false($loaded);
            $assert->same(
                $result2,
                $attempt->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );

            $loaded = false;
            $attempt = Attempt::defer(static function() use (&$loaded, $error1) {
                $loaded = true;

                return Attempt::error($error1);
            })->eitherWay(
                Attempt::result(...),
                Attempt::error(...),
            );
            $assert->false($loaded);
            $assert->same(
                $error1,
                $attempt->match(
                    static fn() => null,
                    static fn($value) => $value,
                ),
            );

            $loaded = false;
            $attempt = Attempt::defer(static function() use (&$loaded, $error1) {
                $loaded = true;

                return Attempt::error($error1);
            })->eitherWay(
                Attempt::error(...),
                Attempt::result(...),
            );
            $assert->false($loaded);
            $assert->same(
                $error1,
                $attempt->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );

            $loaded = false;
            $attempt = Attempt::defer(static function() use (&$loaded, $error1) {
                $loaded = true;

                return Attempt::error($error1);
            })->eitherWay(
                Attempt::result(...),
                static fn() => Attempt::error($error2),
            );
            $assert->false($loaded);
            $assert->same(
                $error2,
                $attempt->match(
                    static fn() => null,
                    static fn($value) => $value,
                ),
            );
        });

    yield $prove
        ->proof('Attempt::guard()')
        ->given(
            Set::integers()->above(1),
            Set::integers()->below(-1),
        )
        ->test(static function($assert, $positive, $negative) {
            $fail = new Exception;
            $assert->same(
                $positive,
                Attempt::result($positive)
                    ->guard(static fn() => Attempt::result($positive))
                    ->recover(static fn() => Attempt::result($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );

            $assert->same(
                $negative,
                Attempt::result($positive)
                    ->guard(static fn() => Attempt::error($fail))
                    ->recover(static fn() => Attempt::result($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );

            $assert->same(
                $fail,
                Attempt::result($positive)
                    ->guard(static fn() => Attempt::error($fail))
                    ->xrecover(static fn() => Attempt::result($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn($e) => $e,
                    ),
            );

            $assert->same(
                $negative,
                Attempt::result($positive)
                    ->flatMap(static fn() => Attempt::error($fail))
                    ->xrecover(static fn() => Attempt::result($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn($e) => $e,
                    ),
            );
        });

    yield $prove
        ->proof('Attempt::defer()->guard()')
        ->given(
            Set::integers()->above(1),
            Set::integers()->below(-1),
        )
        ->test(static function($assert, $positive, $negative) {
            $fail = new Exception;
            $assert->same(
                $positive,
                Attempt::defer(static fn() => Attempt::result($positive))
                    ->guard(static fn() => Attempt::result($positive))
                    ->recover(static fn() => Attempt::result($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );

            $assert->same(
                $negative,
                Attempt::defer(static fn() => Attempt::result($positive))
                    ->guard(static fn() => Attempt::error($fail))
                    ->recover(static fn() => Attempt::result($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn() => null,
                    ),
            );

            $assert->same(
                $fail,
                Attempt::defer(static fn() => Attempt::result($positive))
                    ->guard(static fn() => Attempt::error($fail))
                    ->xrecover(static fn() => Attempt::result($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn($e) => $e,
                    ),
            );

            $assert->same(
                $negative,
                Attempt::defer(static fn() => Attempt::result($positive))
                    ->flatMap(static fn() => Attempt::error($fail))
                    ->xrecover(static fn() => Attempt::result($negative))
                    ->match(
                        static fn($value) => $value,
                        static fn($e) => $e,
                    ),
            );
        });
};
