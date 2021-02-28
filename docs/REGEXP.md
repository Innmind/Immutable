# `RegExp`

This class is here to help make sure that a string is a regular expression so you can safely type against this class.

## `::of()`

This is the named cosntructor for this class.

```php
RegExp::of('/foo/') instanceof RegExp; // true
RegExp::of('foo'); // throws Innmind\Immutable\Exception\DomainException
```

## `->matches()`

Both examples do the same thing.

```php
RegExp::of('/^a/')->matches(Str::of('abcdef'));
Str::of('abcdef')->matches('/^a/');
```

## `->capture()`

Both examples do the same thing.

```php
RegExp::of('@^(?:http://)?(?P<host>[^/]+)@i')->capture(Str::of('http://www.php.net/index.html'));
Str::of('http://www.php.net/index.html')->capture('@^(?:http://)?(?P<host>[^/]+)@i');
```

## `->toString()`

Return the string representation of the regular expression.

```php
RegExp::of('/foo/')->toString(); // '/foo/'
```
