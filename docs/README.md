# Getting Started

This project brings a set of immutable data structure to bring a uniformity on how to handle data.

Before diving in the documentation you may want to read about the [philosophy](PHILOSOPHY.md) behind the structures design.

## Installation

```sh
composer require innmind/immutable
```

## Structures

This library provides the 7 following structures:

- [`Sequence`](SEQUENCE.md)
- [`Set`](SET.md)
- [`Map`](MAP.md)
- [`Str`](STR.md)
- [`RegExp`](REGEXP.md)
- [`Maybe`](MAYBE.md)
- [`Either`](EITHER.md)
- [`State`](STATE.md)
- [`Fold`](FOLD.md)

See the documentation for each structure to understand how to use them.

All structures are typed with [`vimeo/psalm`](https://psalm.dev), you must use it in order to verify that you use this library correctly.

## Use cases

- [How to read a file](LAZY_FILE.md)
- [Parsing strings](PARSING.md)

## Testing

This package provides sets that can be used with [BlackBox](BLACKBOX.md).
