<?php
declare(strict_types = 1);

use Innmind\Immutable\{
    Sequence,
    Sequence\Union\Both,
    Maybe,
    Either,
    Attempt,
    Str,
    Monoid\Concat,
};
use Properties\Innmind\Immutable\Sequence as Properties;
use Innmind\BlackBox\Set;

return static function($prove) {
    yield $prove
        ->proof('Sequence::toIdentity()')
        ->given(Set::sequence(Set::type()))
        ->test(static function($assert, $values) {
            $sequence = Sequence::of(...$values);

            $assert->same(
                $sequence->toList(),
                $sequence
                    ->toIdentity()
                    ->unwrap()
                    ->toList(),
            );
        });

    yield $prove
        ->proof('Sequence::prepend()')
        ->given(
            Set::sequence(Set::type()),
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $first, $second) {
            $assert->same(
                [...$first, ...$second],
                Sequence::of(...$second)
                    ->prepend(Sequence::of(...$first))
                    ->toList(),
            );

            $assert->same(
                [...$first, ...$second],
                Sequence::defer((static function() use ($second) {
                    yield from $second;
                })())
                    ->prepend(Sequence::defer((static function() use ($first) {
                        yield from $first;
                    })()))
                    ->toList(),
            );

            $assert->same(
                [...$first, ...$second],
                Sequence::lazy(static function() use ($second) {
                    yield from $second;
                })
                    ->prepend(Sequence::lazy(static function() use ($first) {
                        yield from $first;
                    }))
                    ->toList(),
            );
        });

    yield $prove
        ->proof('Sequence::chunk()')
        ->given(
            Set::strings()->atLeast(100),
            Set::integers()->between(1, 50),
        )
        ->test(static function($assert, $string, $chunk) {
            $chunks = Str::of($string, Str\Encoding::ascii)
                ->split()
                ->chunk($chunk);

            $_ = $chunks->foreach(
                static fn($chars) => $assert
                    ->number($chars->size())
                    ->lessThanOrEqual($chunk),
            );
            $_ = $chunks
                ->dropEnd(1)
                ->foreach(
                    static fn($chars) => $assert->same(
                        $chunk,
                        $chars->size(),
                    ),
                );

            $assert->same(
                $string,
                $chunks
                    ->flatMap(static fn($chars) => $chars)
                    ->fold(Concat::monoid)
                    ->toString(),
            );
        });

    yield $prove
        ->proof('Sequende::defer() holds intermediary values even when no longer used')
        ->given(
            Set::sequence(Set::type()),
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $prefix, $suffix) {
            $initial = Sequence::defer((static function() use ($prefix, $suffix) {
                foreach ($prefix as $value) {
                    yield $value;
                }

                foreach ($suffix as $value) {
                    yield $value;
                }
            })());

            // This does a partial read on the generator
            $assert->same(
                $prefix,
                $initial
                    ->take(\count($prefix))
                    ->toList(),
            );

            // The maps are only here to wrap the generator, it doesn't change
            // the values
            $another = $initial
                ->map(static fn($value) => [$value])
                ->map(static fn($values) => $values[0]);
            unset($initial);

            // If it didn't store the intermediary values the array would miss
            // the prefix values due to the partial read on the initial
            // generator due to the ->take()->toList() call above
            $assert->same(
                [...$prefix, ...$suffix],
                $another->toList(),
            );
        });

    yield $prove
        ->proof("Sequence::defer() stack trace doesn't show intermediary sequences when not used")
        ->given(Set::integers()->between(1, 10))
        ->test(static function($assert, $calls) {
            $expected = null;
            $sequence = Sequence::defer((static function() use (&$expected) {
                yield null;

                throw $expected = new Exception;
            })());

            for ($i = 0; $i < $calls; $i++) {
                $sequence = $sequence->map(static fn($value) => $value);
            }

            try {
                $_ = $sequence->toList();
                $assert->fail('it should throw');
            } catch (Exception $e) {
                $assert->same($expected, $e);

                $accumulations = \array_filter(
                    $e->getTrace(),
                    static fn($frame) => \str_ends_with($frame['file'] ?? '', 'src/Accumulate.php'),
                );

                $assert->count(1, $accumulations);
            }
        });

    yield $prove
        ->proof('Sequence::sink()->until()')
        ->given(Set::sequence(Set::type()))
        ->test(static function($assert, $values) {
            $all = Sequence::of(...$values)
                ->sink([])
                ->until(static fn($all, $value, $continuation) => $continuation->continue(
                    [...$all, $value],
                ));

            $assert->same($values, $all);

            $none = Sequence::of(...$values)
                ->sink([])
                ->until(static fn($all, $value, $continuation) => $continuation->stop(
                    $all,
                ));

            $assert->same([], $none);
        });

    yield $prove
        ->proof('Sequence::sink()->until() when deferred')
        ->given(Set::sequence(Set::type()))
        ->test(static function($assert, $values) {
            $all = Sequence::defer((static function() use ($values) {
                yield from $values;
            })())
                ->sink([])
                ->until(static fn($all, $value, $continuation) => $continuation->continue(
                    [...$all, $value],
                ));

            $assert->same($values, $all);

            $none = Sequence::defer((static function() use ($values) {
                yield from $values;
            })())
                ->sink([])
                ->until(static fn($all, $value, $continuation) => $continuation->stop(
                    $all,
                ));

            $assert->same([], $none);
        });

    yield $prove
        ->proof("Sequence::sink()->until() when deferred doesn't load values after stop")
        ->given(
            Set::sequence(Set::type()),
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $prefix, $suffix) {
            $stop = new stdClass;
            $loaded = false;
            $all = Sequence::defer((static function() use ($prefix, $suffix, $stop, &$loaded) {
                yield from $prefix;
                yield $stop;
                $loaded = true;
                yield from $suffix;
            })())
                ->sink([])
                ->until(static fn($all, $value, $continuation) => match ($value) {
                    $stop => $continuation->stop($all),
                    default => $continuation->continue(
                        [...$all, $value],
                    ),
                });

            $assert->same($prefix, $all);
            $assert->false($loaded);
        });

    yield $prove
        ->proof('Sequence::sink()->until() when lazy')
        ->given(Set::sequence(Set::type()))
        ->test(static function($assert, $values) {
            $all = Sequence::lazy(static function() use ($values) {
                yield from $values;
            })
                ->sink([])
                ->until(static fn($all, $value, $continuation) => $continuation->continue(
                    [...$all, $value],
                ));

            $assert->same($values, $all);

            $none = Sequence::lazy(static function() use ($values) {
                yield from $values;
            })
                ->sink([])
                ->until(static fn($all, $value, $continuation) => $continuation->stop(
                    $all,
                ));

            $assert->same([], $none);
        });

    yield $prove
        ->proof("Sequence::sink()->until() when lazy doesn't load values after stop")
        ->given(
            Set::sequence(Set::type()),
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $prefix, $suffix) {
            $stop = new stdClass;
            $loaded = false;
            $all = Sequence::lazy(static function() use ($prefix, $suffix, $stop, &$loaded) {
                yield from $prefix;
                yield $stop;
                $loaded = true;
                yield from $suffix;
            })
                ->sink([])
                ->until(static fn($all, $value, $continuation) => match ($value) {
                    $stop => $continuation->stop($all),
                    default => $continuation->continue(
                        [...$all, $value],
                    ),
                });

            $assert->same($prefix, $all);
            $assert->false($loaded);
        });

    yield $prove
        ->proof('Sequence::sink()->until() when lazy cleans up on stop')
        ->given(
            Set::sequence(Set::type()),
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $prefix, $suffix) {
            $stop = new stdClass;
            $cleaned = false;
            $all = Sequence::lazy(static function($register) use ($prefix, $suffix, $stop, &$cleaned) {
                $register(static function() use (&$cleaned) {
                    $cleaned = true;
                });
                yield from $prefix;
                yield $stop;
                yield from $suffix;
            })
                ->sink([])
                ->until(static fn($all, $value, $continuation) => match ($value) {
                    $stop => $continuation->stop($all),
                    default => $continuation->continue(
                        [...$all, $value],
                    ),
                });

            $assert->same($prefix, $all);
            $assert->true($cleaned);
        });

    yield $prove
        ->proof('Sequence::sink()->maybe()')
        ->given(
            Set::sequence(Set::type()),
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $prefix, $suffix) {
            $all = Sequence::of(...$prefix, ...$suffix)
                ->sink([])
                ->maybe(static fn($all, $value) => Maybe::just(
                    [...$all, $value],
                ));

            $assert->same(
                [...$prefix, ...$suffix],
                $all->match(
                    static fn($all) => $all,
                    static fn() => null,
                ),
            );

            $stop = new stdClass;
            $all = Sequence::of(...$prefix, ...[$stop], ...$suffix)
                ->sink([])
                ->maybe(static fn($all, $value) => match ($value) {
                    $stop => Maybe::nothing(),
                    default => Maybe::just(
                        [...$all, $value],
                    ),
                });

            $assert->null(
                $all->match(
                    static fn($all) => $all,
                    static fn() => null,
                ),
            );
        });

    yield $prove
        ->proof('Sequence::sink()->either()')
        ->given(
            Set::sequence(Set::type()),
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $prefix, $suffix) {
            $all = Sequence::of(...$prefix, ...$suffix)
                ->sink([])
                ->either(static fn($all, $value) => Either::right(
                    [...$all, $value],
                ));

            $assert->same(
                [...$prefix, ...$suffix],
                $all->match(
                    static fn($all) => $all,
                    static fn() => null,
                ),
            );

            $stop = new stdClass;
            $all = Sequence::of(...$prefix, ...[$stop], ...$suffix)
                ->sink([])
                ->either(static fn($all, $value) => match ($value) {
                    $stop => Either::left($all),
                    default => Either::right(
                        [...$all, $value],
                    ),
                });

            $assert->same(
                $prefix,
                $all->match(
                    static fn() => null,
                    static fn($all) => $all,
                ),
            );
        });

    yield $prove
        ->proof('Sequence::sink()->attempt()')
        ->given(
            Set::sequence(Set::type()),
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $prefix, $suffix) {
            $all = Sequence::of(...$prefix, ...$suffix)
                ->sink([])
                ->attempt(static fn($all, $value) => Attempt::result(
                    [...$all, $value],
                ));

            $assert->same(
                [...$prefix, ...$suffix],
                $all->match(
                    static fn($all) => $all,
                    static fn() => null,
                ),
            );

            $stop = new Exception;
            $all = Sequence::of(...$prefix, ...[$stop], ...$suffix)
                ->sink([])
                ->attempt(static fn($all, $value) => match ($value) {
                    $stop => Attempt::error($stop),
                    default => Attempt::result(
                        [...$all, $value],
                    ),
                });

            $assert->same(
                $stop,
                $all->match(
                    static fn($all) => $all,
                    static fn($e) => $e,
                ),
            );
        });

    yield $prove
        ->proof('Sequence::lazy()->take() should not load an extra element')
        ->given(
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $values) {
            $sequence = Sequence::lazy(static function() use ($values) {
                yield from $values;

                throw new Exception;
            })->take(\count($values));

            $assert->not()->throws(
                static fn() => $assert->same(
                    $values,
                    $sequence->toList(),
                ),
            );
        });

    yield $prove
        ->proof('Sequence::defer()->take() should not load an extra element')
        ->given(
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $values) {
            $sequence = Sequence::defer((static function() use ($values) {
                yield from $values;

                throw new Exception;
            })())->take(\count($values));

            $assert->not()->throws(
                static fn() => $assert->same(
                    $values,
                    $sequence->toList(),
                ),
            );
        });

    yield $prove->test(
        'Partial load a deferred Sequence appended to a lazy one',
        static function($assert) {
            $lazy = Sequence::lazy(static function() {
                yield 1;
                yield 2;
                yield 3;
                yield 4;
                yield 5;
            });
            $defer = Sequence::defer((static function() {
                yield 6;
                yield 7;
                yield 8;
                yield 9;
                yield 10;
            })());

            $assert->same(
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 6, 7, 8, 9, 10],
                $lazy
                    ->append($defer)
                    ->take(7)
                    ->flatMap(static fn($i) => match (true) {
                        $i > 5 => $defer,
                        default => Sequence::of($i),
                    })
                    ->toList(),
            );
            $assert->same(
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 6, 7, 8, 9, 10],
                $defer
                    ->prepend($lazy)
                    ->take(7)
                    ->flatMap(static fn($i) => match (true) {
                        $i > 5 => $defer,
                        default => Sequence::of($i),
                    })
                    ->toList(),
            );
        },
    );

    yield $prove->test(
        'Sequence::defer()->zip() with a partially loaded lazy Sequence',
        static function($assert) {
            $defer = Sequence::defer((static function() {
                yield 1;
                yield 2;
                yield 3;
            })());
            $cleaned = false;
            $loaded = false;
            $lazy = Sequence::lazy(static function($register) use (&$cleaned, &$loaded) {
                $register(static function() use (&$cleaned) {
                    $cleaned = true;
                });

                yield 4;
                yield 5;
                yield 6;
                yield 7;
                $loaded = true;
            });

            $assert->same(
                [[1, 4], [2, 5], [3, 6]],
                $defer
                    ->zip($lazy)
                    ->toList(),
            );
            $assert->true($cleaned);
            $assert->false($loaded);
            $assert->same([1, 2, 3], $defer->toList());
            $assert->same([4, 5, 6, 7], $lazy->toList());
        },
    );

    yield $prove->test(
        'Lazy Sequence::toSet()',
        static function($assert) {
            $loaded = false;
            $lazy = Sequence::lazy(static function() use (&$loaded) {
                yield 1;
                yield 2;
                yield 3;
                $loaded = true;
            });

            $set = $lazy->toSet();
            $assert->false($loaded);
            $assert->same([1, 2, 3], $set->toList());
            $assert->true($loaded);
        },
    );

    yield $prove->test(
        'Deferred Sequence::filter() is iterable twice',
        static function($assert) {
            $defer = Sequence::defer((static function() {
                yield 1;
                yield 2;
                yield 3;
                yield 4;
            })());
            $odd = $defer->filter(static fn($i) => $i%2 === 0);

            $assert->same([2, 4], $odd->toList());
            $assert->same([2, 4], $odd->toList());
            $assert->same([1, 2, 3, 4], $defer->toList());
        },
    );

    yield $prove->test(
        'Consuming out of order deferred sequences',
        static function($assert) {
            $source = Sequence::defer((static function() {
                yield from \range(0, 10);
            })());
            $initial = $source->filter(static fn($i) => $i%2 === 0);
            $other = $source->filter(static fn() => false);
            unset($source);

            $assert->false($initial->equals($other));
        },
    );

    yield $prove->test(
        'Calling first inside a lazy Sequence::flatMap()',
        static function($assert) {
            $lazy = Sequence::lazy(static function() {
                yield 1;
                yield 2;
                yield 3;
            });

            $assert->same(
                [1, 1, 1],
                $lazy
                    ->flatMap(static fn() => $lazy->first()->toSequence())
                    ->toList(),
            );
        },
    );

    yield $prove
        ->proof('Sequence::aggregate() should not alter the initial Sequence by default')
        ->given(Set::sequence(Set::type()))
        ->test(static function($assert, $values) {
            $inMemory = Sequence::of(...$values);

            $assert->same(
                $values,
                $inMemory
                    ->aggregate(static fn($a, $b) => Sequence::of($a, $b))
                    ->toList(),
            );

            $defer = Sequence::defer((static function() use ($values) {
                yield from $values;
            })());

            $assert->same(
                $values,
                $defer
                    ->aggregate(static fn($a, $b) => Sequence::of($a, $b))
                    ->toList(),
            );

            $lazy = Sequence::lazy(static function() use ($values) {
                yield from $values;
            });

            $assert->same(
                $values,
                $lazy
                    ->aggregate(static fn($a, $b) => Sequence::of($a, $b))
                    ->toList(),
            );
        });

    yield $prove
        ->proof('Sequence::snap() loads a lazy sequence only once')
        ->given(
            Set::type(),
            Set::sequence(Set::type()),
            Set::type(),
        )
        ->test(static function($assert, $value, $rest, $last) {
            $loaded = 0;
            $sequence = Sequence::lazy(static function() use (&$loaded, $value, $rest, $last) {
                yield $value;
                yield from $rest;
                yield $last;

                ++$loaded;
            })
                ->snap()
                ->map(static fn($value) => [$value]);

            $assert->same(0, $loaded);
            $assert->same(
                [$value],
                $sequence->first()->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->same(1, $loaded);
            $assert->same(
                [$last],
                $sequence->last()->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->same(1, $loaded);
        });

    yield $prove
        ->proof('Sequence::snap() keeps in memory intermediary steps')
        ->given(
            Set::type(),
            Set::sequence(Set::type()),
            Set::type(),
        )
        ->test(static function($assert, $value, $rest, $last) {
            $loaded = 0;
            $snapped = Sequence::lazy(static function() use (&$loaded, $value, $rest, $last) {
                yield $value;
                yield from $rest;
                yield $last;

                ++$loaded;
            })->snap();
            $mapped = $snapped->map(static fn($value) => [$value]);

            $assert->same(0, $loaded);
            $assert->same(
                [$value],
                $mapped->first()->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->same(1, $loaded);
            $assert->same(
                [$last],
                $mapped->last()->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->same(1, $loaded);

            $assert->same(
                $last,
                $snapped->last()->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->same(1, $loaded);
        });

    yield $prove
        ->proof('Sequence::snap() loads a deferred sequence at once')
        ->given(
            Set::type(),
            Set::sequence(Set::type()),
            Set::type(),
        )
        ->test(static function($assert, $value, $rest, $last) {
            $loaded = 0;
            $sequence = Sequence::defer((static function() use (&$loaded, $value, $rest, $last) {
                yield $value;
                yield from $rest;
                yield $last;

                ++$loaded;
            })())
                ->snap()
                ->map(static fn($value) => [$value]);

            $assert->same(0, $loaded);
            $assert->same(
                [$value],
                $sequence->first()->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->same(1, $loaded);
            $assert->same(
                [$last],
                $sequence->last()->match(
                    static fn($value) => $value,
                    static fn() => null,
                ),
            );
            $assert->same(1, $loaded);
        });

    yield $prove
        ->proof('Sequence::via()')
        ->given(
            Set::sequence(Set::type()),
            Set::of(
                static fn(array $values) => Sequence::of(...$values),
                static fn(array $values) => Sequence::defer(
                    (static fn() => yield from $values)(),
                ),
                static fn(array $values) => Sequence::lazy(
                    static fn() => yield from $values,
                ),
            ),
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $initial, $build, $new) {
            $sequence = $build($initial);
            $sequence2 = $sequence->via(static function($sequence) use ($assert, $initial, $build, $new) {
                $assert->same($initial, $sequence->toList());

                return $build($new);
            });

            $assert->same($new, $sequence2->toList());
        });

    yield $prove
        ->proof("Sequence::via() doesn't load underlying generators")
        ->given(
            Set::sequence(Set::type()),
            Set::of(
                static fn(bool &$loaded, array $values) => Sequence::defer(
                    (static function() use (&$loaded, $values) {
                        yield from $values;
                        $loaded = true;
                    })(),
                ),
                static fn(bool &$loaded, array $values) => Sequence::lazy(
                    static function() use (&$loaded, $values) {
                        yield from $values;
                        $loaded = true;
                    },
                ),
            ),
        )
        ->test(static function($assert, $initial, $build) {
            $loaded = false;
            $sequence = $build($loaded, $initial);
            $sequence2 = $sequence->via(static fn($sequence) => $sequence);

            $assert->false($loaded);
            $assert->same($initial, $sequence2->toList());
        });

    yield $prove
        ->proof('Sequence::via() on a lazy sequence calls the function everytime the sequence is unwrapped')
        ->given(
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $values) {
            $loaded = 0;
            $sequence = Sequence::lazy(
                static fn() => yield from $values,
            );
            $sequence2 = $sequence->via(static function($sequence) use (&$loaded) {
                ++$loaded;

                return $sequence;
            });

            $assert->same(0, $loaded);
            $assert->same($values, $sequence2->toList());
            $assert->same(1, $loaded);
            $assert->same($values, $sequence2->toList());
            $assert->same(2, $loaded);
        });

    yield $prove
        ->proof('Sequence::lazy()->snap()->via() loads the generator once')
        ->given(
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $values) {
            $loaded = 0;
            $sequence = Sequence::lazy(
                static fn() => yield from $values,
            );
            $sequence2 = $sequence
                ->snap()
                ->via(static function($sequence) use (&$loaded) {
                    ++$loaded;

                    return $sequence;
                });

            $assert->same(0, $loaded);
            $assert->same($values, $sequence2->toList());
            $assert->same(1, $loaded);
            $assert->same($values, $sequence2->toList());
            $assert->same(1, $loaded);
        });

    yield $prove
        ->proof('Sequence::lazy()->snap()->via() loads the initial snapped sequence only once')
        ->given(
            Set::sequence(Set::type()),
        )
        ->test(static function($assert, $values) {
            $loaded = 0;
            $sequence = Sequence::lazy(
                static function() use ($values, &$loaded) {
                    yield from $values;
                    ++$loaded;
                },
            )->snap();
            $sequence2 = $sequence->via(static fn($sequence) => $sequence);

            $assert->same(0, $loaded);
            $assert->same($values, $sequence2->toList());
            $assert->same(1, $loaded);
            $assert->same($values, $sequence2->toList());
            $assert->same(1, $loaded);
            $assert->same($values, $sequence->toList());
            $assert->same(1, $loaded);
        });

    yield $prove
        ->proof('Sequence->snap()->toSet()')
        ->given(
            Set::sequence(Set::integers()),
            Set::of(
                static fn(array $values) => Sequence::of(...$values),
                static fn(array $values) => Sequence::defer(
                    (static fn() => yield from $values)(),
                ),
                static fn(array $values) => Sequence::lazy(
                    static fn() => yield from $values,
                ),
            ),
        )
        ->test(static function($assert, $values, $build) {
            $assert->same(
                \array_unique($values),
                $build($values)
                    ->snap()
                    ->toSet()
                    ->toList(),
            );
        });

    yield $prove
        ->proof('Sequence::lazy()->snap()->toSet() only load the generator once')
        ->given(
            Set::sequence(Set::integers()),
        )
        ->test(static function($assert, $values) {
            $loaded = 0;
            $sequence = Sequence::lazy(static function() use ($values, &$loaded) {
                yield from $values;
                ++$loaded;
            })->snap();
            $set = $sequence->toSet();

            $assert->same(
                \array_unique($values),
                $set->toList(),
            );
            $assert->same(1, $loaded);
            $assert->same($values, $sequence->toList());
            $assert->same(1, $loaded);
        });

    yield $prove
        ->proof('Sequence::lazy()->snap() should not keep the original sequence when no longer used')
        ->given(
            Set::sequence(Set::integers()),
        )
        ->test(static function($assert, $values) {
            $beacon = new stdClass;
            $sequence = Sequence::lazy(static function() use ($values, $beacon) {
                yield from $values;
            })->snap();
            $beacon = WeakReference::create($beacon);

            $assert->object($beacon->get());
            $assert->same($values, $sequence->toList());
            $assert->null($beacon->get());
            $assert->same($values, $sequence->toList());
        });

    yield $prove
        ->proof('Sequence::lazy()->snap()->toSet() should keep values in memory')
        ->given(
            Set::sequence(Set::integers()),
        )
        ->test(static function($assert, $values) {
            $set = Sequence::lazy(static fn() => yield from $values)
                ->snap()
                ->toSet()
                ->map(static fn() => new stdClass);

            $assert->same(
                $set->toList(),
                $set->toList(),
            );
        });

    yield $prove
        ->proof('Sequence::equals() should not load its data when compared to itself')
        ->given(Set::sequence(Set::type()))
        ->test(static function($assert, $values) {
            $loaded = false;
            $sequence = Sequence::lazy(static function() use (&$loaded, $values) {
                $loaded = true;

                yield from $values;
            });

            $assert->true($sequence->equals($sequence));
            $assert->false($loaded);
        });

    yield $prove->test(
        'Sequence::union()',
        static function($assert) {
            $assert->same(
                [1, 2, 3],
                Sequence::of(1, 2, 3)
                    ->union(Sequence::of())
                    ->map(static fn($left) => $left->unwrap())
                    ->toList(),
            );
            $assert->same(
                [1, 2, 3],
                Sequence::of()
                    ->union(Sequence::of(1, 2, 3))
                    ->map(static fn($right) => $right->unwrap())
                    ->toList(),
            );
            $assert->same(
                [[1, 4], [2, 5], [3, 6]],
                Sequence::of(1, 2, 3)
                    ->union(Sequence::of(4, 5, 6))
                    ->map(static fn($both) => [
                        $both->left(),
                        $both->right(),
                    ])
                    ->toList(),
            );
            $assert->same(
                [[1, 4], [2, 5], [3, null]],
                Sequence::of(1, 2, 3)
                    ->union(Sequence::of(4, 5))
                    ->map(static fn($both) => [
                        $both->left(),
                        match (true) {
                            $both instanceof Both => $both->right(),
                            default => null,
                        },
                    ])
                    ->toList(),
            );
            $assert->same(
                [[1, 4], [2, 5], [null, 6]],
                Sequence::of(1, 2)
                    ->union(Sequence::of(4, 5, 6))
                    ->map(static fn($both) => [
                        match (true) {
                            $both instanceof Both => $both->left(),
                            default => null,
                        },
                        $both->right(),
                    ])
                    ->toList(),
            );
        },
    );

    $values = Set::sequence(Set::type());
    $sequences = Set::either(
        $values->map(static fn($values) => static fn() => Sequence::of(...$values)),
        $values->map(
            static fn($values) => static fn() => Sequence::lazy(
                static fn() => yield from $values,
            ),
        ),
        $values->map(
            static fn($values) => static fn() => Sequence::defer(
                (static fn() => yield from $values)(),
            ),
        ),
    );

    yield $prove->properties(
        'Sequence',
        Properties::properties(),
        $sequences,
    );

    foreach (Properties::list() as $property) {
        yield $prove->property(
            $property,
            $sequences,
        );
    }
};
