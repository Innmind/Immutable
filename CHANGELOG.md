# Changelog

## [Unreleased]

### Fixed

- `Sequence->snap()->toSet()` wasn't properly keeping values in memory (transformations were lazy)

## 5.14.2 - 2025-05-02

### Fixed

- `Sequence->snap()` on an already loaded sequence created useless objects
- `Sequence->snap()` wasn't keeping intermediary steps in memory

## 5.14.1 - 2025-04-02

### Fixed

- `Sequence->snap()->toSet()` being recursive

## 5.14.0 - 2025-04-02

### Added

- `Innmind\Immutable\Sequence::snap()`
- `Innmind\Immutable\Set::snap()`
- `Innmind\Immutable\Sequence::via()`

### Changed

- `Innmind\Immutable\Set` implementation now directly uses `Innmind\Immutable\Sequence`

### Deprecated

- `Innmind\Immutable\Set::defer()`
- `Innmind\Immutable\Sequence::indexOf()`

## 5.13.0 - 2025-03-23

### Added

- `Innmind\Immutable\Sequence::sink()->attempt()`

## 5.12.0 - 2025-03-19

### Added

- Support for `innmind/black-box` `6`
- `Innmind\Immutable\SideEffect::identity()`

### Deprecated

- `Innmind\Immutable\SideEffect::__construct()`, use `Innmind\Immutable\SideEffect::identity()` instead

## 5.11.3 - 2025-02-26

### Changed

- The function passed to `RegisterCleanup` for lazy `Set`s or `Sequence`s is not called when the monad is partially loaded when called from a deferred/in memory `Set`/`Sequence` (such as `Sequence::zip()`, a call to `find` inside a `flatMap`, etc...).

### Fixed

- When a deferred `Set` or `Sequence` is used while iterating over itself it could produce unexpected results such as infinite loops or skipped values.
- Fix iterating over a closed `\Generator` on a deferred `Set`/`Sequence` when the source monad no longer exist and the monad at hand has already been iterated over.
- A deferred `Sequence` would load an extra element that is never used when calling `take`.

## 5.11.2 - 2025-02-23

### Fixed

- A lazy `Sequence` would load an extra element that is never used when calling `take`

## 5.11.1 - 2025-01-16

### Fixed

- Support for PHP `8.4`

## 5.11.0 - 2024-12-01

### Added

- `Innmind\Immutable\Sequence::sink()`
- `Innmind\Immutable\Attempt`

### Fixed

- `Innmind\Immutable\Maybe::memoize()` and `Innmind\Immutable\Either::memoize()` was only unwrapping the first layer of the monad. It now recursively unwraps until all the deferred monads are memoized.

## 5.10.0 - 2024-11-09

### Added

- `Innmind\Immutable\Map::toSequence()`

### Changed

- Use `static` closures as much as possible to reduce the probability of creating circular references by capturing `$this` as it can lead to memory root buffer exhaustion.
- Remove keeping intermediary values of a deferred `Sequence` that is referenced by no one.

### Deprecated

- `Innmind\Immutable\State`
- `Innmind\Immutable\Fold`

### Fixed

- Using `string`s or `int`s as a `Map` key type and then adding keys of different types was throwing an error.

## 5.9.0 - 2024-07-05

### Added

- `Innmind\Immutable\Sequence::chunk()`

## 5.8.0 - 2024-06-27

### Added

- `Innmind\Immutable\Identity::lazy()`
- `Innmind\Immutable\Identity::defer()`
- `Innmind\Immutable\Identity::toSequence()`

### Changed

- `Innmind\Immutable\Sequence::toIdentity()` returns a lazy, deferred or in memory `Identity` based on the kind of `Sequence`

## 5.7.0 - 2024-06-25

### Added

- `Innmind\Immutable\Sequence::prepend()`

## 5.6.0 - 2024-06-15

### Added

- `Innmind\Immutable\Identity`
- `Innmind\Immutable\Sequence::toIdentity()`

## 5.5.0 - 2024-06-02

### Changed

- A lazy `Sequence::takeEnd()` no longer loads the whole sequence in memory, only the number of elements taken + 1.

## 5.4.0 - 2024-05-29

### Added

- `Innmind\Immutable\Set::unsorted()`

## 5.3.0 - 2023-11-06

### Added

- `Innmind\Immutable\Validation`

## 5.2.0 - 2023-11-05

### Added

- `Innmind\Immutable\Set::match()`
- `Innmind\Immutable\Predicate\OrPredicate`
- `Innmind\Immutable\Predicate\AndPredicate`
- `Innmind\Immutable\Predicate\Instance::or()`
- `Innmind\Immutable\Predicate\Instance::and()`

## 5.1.0 - 2023-10-11

### Changed

- Registered cleanup callbacks for lazy `Sequence`s and `Set`s are all called now for composed structures, instead of the last one

## 5.0.0 - 2023-09-16

### Changed

- `Innmind\Immutable\Str` only use `Innmind\Immutable\Str\Encoding` to represent the encoding to work with

### Removed

- `Fixtures\Innmind\Immutable\Map`

## 4.15.0 - 2023-07-08

### Added

- `Innmind\Immutable\Str\Encoding`
- `Innmind\Immutable\Str` now implements `\Stringable`
- Most `Innmind\Immutable\Str` methods now also accept `\Stringable`

### Changed

- `innmind/black-box` updated to version `5`

### Removed

- Support for PHP `8.0` and `8.1`

## 4.14.1 - 2023-05-18

### Changed

- All `reduce` methods now explicit the fact that the callable may not be called when the structure is empty

### Fixed

- A lazy `Sequence::slice()` no longer loads the whole underlying `Generator`
- `Innmind\Immutable\Set::matches()`, `Innmind\Immutable\Sequence::matches()` and `Innmind\Immutable\Map::matches()` no longer iterates over all elements when one value doesn't match the predicate
- When using `yield from` in the `Generator` passed to `Sequence::lazy()` values may be lost on certain operations

## 4.14.0 - 2023-04-29

### Added

- `Innmind\Immutable\Either::flip()`
- `Innmind\Immutable\Maybe::toSequence()`
- `Innmind\Immutable\Maybe::eitherWay()`
- `Innmind\Immutable\Either::eitherWay()`

## 4.13.0 - 2023-04-10

### Added

- `Innmind\Immutable\Maybe::memoize()`
- `Innmind\Immutable\Either::memoize()`
- `Innmind\Immutable\Sequence::memoize()`
- `Innmind\Immutable\Set::memoize()`
- `Innmind\Immutable\Sequence::toSet()`
- `Innmind\Immutable\Sequence::dropWhile()`
- `Innmind\Immutable\Sequence::takeWhile()`

### Changed

- Monads templates are now covariant

## 4.12.0 - 2023-03-30

### Added

- `Innmind\Immutable\Sequence::aggregate()`

## 4.11.0 - 2023-02-18

### Added

- `Innmind\Immutable\Fold`

## 4.10.0 - 2023-02-05

### Added

- `Innmind\Immutable\Str::maybe()`
- `Innmind\Immutable\Maybe::defer()`
- `Innmind\Immutable\Either::defer()`

### Changed

- `->get()`, `->first()`, `->last()`, `->indexOf()` and `->find()` calls on a deferred or lazy `Innmind\Immutable\Sequence` will now return a deferred `Innmind\Immutable\Maybe`

### Fixed

- `Innmind\Immutable\Sequence::last()` returned `Innmind\Immutable\Maybe::nothing()` when the last value was `null`, now it returns `Innmind\Immutable\Maybe::just(null)`

## 4.9.0 - 2022-12-17

### Changed

- Support lazy and deferred `Set::flatMap()`

## 4.8.0 - 2022-12-11

### Added

- `Innmind\Immutable\Sequence::safeguard`
- `Innmind\Immutable\Set::safeguard`

### Fixed

- `Innmind\Immutable\Set::remove()` no longer unwraps deferred and lazy `Set`s
- Fix calling unnecessary methods for some `Set` operations

## 4.7.1 - 2022-11-27

### Fixed

- Fixed `Sequence::lazyStartingWith()` return type declaration

## 4.7.0 - 2022-11-26

### Added

- `Innmind\Immutable\Sequence::lazyStartingWith()`

## 4.6.0 - 2022-10-08

## Added

- `Innmind\Immutable\Map::exclude()`
- `Innmind\Immutable\Maybe::exclude()`
- `Innmind\Immutable\Maybe::keep()`
- `Innmind\Immutable\Sequence::exclude()`
- `Innmind\Immutable\Sequence::keep()`
- `Innmind\Immutable\Set::exclude()`
- `Innmind\Immutable\Set::keep()`
- `Innmind\Immutable\Predicate`
- `Innmind\Immutable\Predicate\Instance`

## 4.5.0 - 2022-09-18

### Added

- `Innmind\Immutable\Sequence::zip()`

## 4.4.0 - 2022-07-14

### Added

- `Innmind\Immutable\Maybe::either()`

## 4.3.0 - 2022-07-02

### Added

- `Innmind\Immutable\Either::maybe()`

## 4.2.0 - 2022-03-26

### Added

- `Innmind\Immutable\Monoid` interface
- `Innmind\Immutable\Monoid\Concat`
- `Innmind\Immutable\Monoid\Append`
- `Innmind\Immutable\Monoid\MergeSet`
- `Innmind\Immutable\Monoid\MergeMap`
- `Innmind\Immutable\Sequence::fold(Innmind\Immutable\Monoid)`
