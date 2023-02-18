# Getting Started

This project brings a set of immutable data structure to bring a uniformity on how to handle data.

## Installation

```sh
composer require innmind/immutable
```

## Usage

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
