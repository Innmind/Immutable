---
currentMenu: philosophy
---

# Philosophy

This project was born after working with other programming languages (like [Scala](https://scala-lang.org)) and discovering [functional programming](https://en.wikipedia.org/wiki/Functional_programming). This taught me 2 things:
- • higher order functions on data structures
- • immutability

## Higher order functions

PHP comes with a handful of functions to work on arrays and strings but many are missing for common use cases forcing us to implement then again and again.

Examples for such cases are a `group` function on a map/set/sequence or `endsWith` on a string. `group` is a specific `reduce` function where the value computed is a map. `endsWith` is also a specific `substring` function.

Higher order functions are built on top of smaller, more abstract, ones. Scala, and other languages, provides common ones on their data structures. And this project is heavily inspired from them.

## Immutability

One of the core principles of functional programming is that data structures cannot change, you can only create a modified copy of them. This is extremely powerful as you can blindly give your data as a function argument with the certainty that it will not be altered. It allows you only focus on the function you are working one, without the need to worry it will have a side effect in other function, or another function triggering a side effect in yours.

Another aspect of immutability is the notion of implicit state. For example in PHP an array is considered immutable because when you give it as a function argument it will create a copy of the variable. But with it comes an implicit state which is its cursor, and it is not reinitialised when a copy is created.

This implicit state can generate subtle bugs in your code, that's why the structures in this project don't implement the `\Iterator` interface in order to always expose complete functions.
