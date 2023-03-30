# Changelog

## [Unreleased]

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
