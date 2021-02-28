# Types

`Map`, `Set` and `Sequence` forces you to specify the type of the elements they contain. You can use:

- any primitive that as a `is_{primitive}` builtin function, such as `int`, `float`, `string`, `array`, `object` and so on..
- `mixed` meaning it can be everything
- `variable` which is an alias for the union type `scalar|array`
- prefix the type with `?` to indicate it is nullable
- use any fully qualified class name
- build unions with with `|` operator, such as `int|string|null|MyClassName`
