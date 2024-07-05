# Changelog

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
