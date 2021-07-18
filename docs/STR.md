# `Str`

This class gives a higher api to manipulate strings.

## `::of()`

This named constructor will create a new object for the given string.

```php
use Innmind\Immutable\Str;

$str = Str::of('whatever');
$str instanceof Str; // true
```

You can also specify the encoding to use for manupilating the string.

```php
$str = Str::of('👋', 'UTF-8');
$str->length(); // 1
Str::of('👋')->length(); // 4
```

## `->toString()`

This will return the encapsulated string.

```php
Str::of('whataver')->toString(); // 'whatever'
```

## `->encoding()`

This will return the encoding use to manipulate the string.

```php
Str::of('', 'UTF-8')->encoding()->equals(Str::of('UTF-8')); // true
```

## `->toEncoding()`

Use this method to change the encoding used to manipulate the string.

```php
Str::of('👋')->toEncoding('UTF-8');
```

## `->split()`

Use this method to split a string into a [`Sequence`](sequence.html) of smaller strings.

```php
Str::of('foo')->split()->equals(Sequence::of(
    Str::class,
    Str::of('f'),
    Str::of('o'),
    Str::of('o'),
));
Str::of('foo|bar')->split('|')->equals(Sequence::of(
    Str::class,
    Str::of('foo'),
    Str::of('bar'),
));
```

## `->chunk()`

This will create a [`Sequence`](sequence.html) of strings of the given size.

```php
Str::of('foobar')->chunk(2)->equals(Sequence::of(
    Str::class,
    Str::of('fo'),
    Str::of('ob'),
    Str::of('ar'),
));
```

## `->position()`

Returns the position of the searched string in the original string.

```php
Str::of('foobar')->position('ob'); // 2
Str::of('foobar')->position('unknown'); // throws Innmind\Immutable\Exception\SubstringException
```

## `->replace()`

Replace the searched string by its replacement.

```php
Str::of('foobar')->replace('ob', 'bo')->equals(Str::of('foboar')); // true
```

## `->str()`

Returns the string following the given delimiter.

```php
Str::of('foobar')->str('b')->equals(Str::of('bar')); // true
Str::of('foobar')->str('unknown'); // throws Innmind\Immutable\Exception\SubstringException
```

## `->toUpper()`

Return the string in upper case.

```php
Str::of('foobar')->toUpper()->equals(Str::of('FOOBAR'));
```

## `->toLower()`

Return the string in lower case.

```php
Str::of('FOOBAR')->toUpper()->equals(Str::of('foobar'));
```

## `->length()`

Returns the length of the string depending on the used encoding.

```php
Str::of('👋', 'UTF-8')->length(); // 1
Str::of('👋')->length(); // 4
```

## `->empty()`

Check if the string is an empty string.

```php
Str::of('')->empty(); // true
Str::of('', 'UTF-8')->empty(); // true
Str::of('null')->empty(); // false
Str::of('0')->empty(); // false
Str::of('false')->empty(); // false
```

## `->reverse()`

Reverse the order of the characters.

```php
Str::of('foobar')->reverse()->equals(Str::of('raboof'));
```

## `->rightPad()`

Add the given string to the right of the string in order of the new string to be at least of the given size.

```php
Str::of('Alien')->rightPad(10)->equals(Str::of('Alien     '));
Str::of('Alien')->rightPad(10, '_')->equals(Str::of('Alien_____'));
Str::of('Alien')->rightPad(3, '_')->equals(Str::of('Alien'));
```

## `->leftPad()`

Add the given string to the left of the string in order of the new string to be at least of the given size.

```php
Str::of('Alien')->leftPad(10)->equals(Str::of('     Alien'));
Str::of('Alien')->leftPad(10, '_')->equals(Str::of('_____Alien'));
Str::of('Alien')->leftPad(3, '_')->equals(Str::of('Alien'));
```

## `->rightPad()`

Add the given string to both sides of the string in order of the new string to be at least of the given size.

```php
Str::of('Alien')->uniPad(10,)->equals(Str::of('  Alien   '));
Str::of('Alien')->uniPad(10, '_')->equals(Str::of('__Alien___'));
```

## `->repeat()`

Repeat the original string the number of given times.

```php
Str::of('foo')->repeat(3)->equals(Str::of('foofoofoo'));
```

## `->stripSlashes()`

Same behaviour as the native `stripslashes` function.

## `->wordCount()`

Counts the number in the string.

```php
Str::of('foo bar')->wordCount(); // 2
```

## `->words()`

The list of words with their position.

```php
Str::of('foo bar')->words()->equals(
    Map::of('int', Str::class)
        (0, Str::of('foo'))
        (4, Str::of('bar')),
);
```

## `->pregSplit()`

Split the string using a regular expression.

```php
Str::of('hypertext language, programming')->pregSplit('/[\s,]+/')->equals(
    Sequence::of(
        Str::class,
        Str::of('hypertext'),
        Str::of('language'),
        Str::of('programming'),
    ),
);
```

## `->matches()`

Check if the string match the given regular expression.

```php
Str::of('abcdef')->matches('/^a/'); // true
Str::of('abcdef')->matches('/^b/'); // false
```

## `->capture`

Return a map of the elements matching the regular expression.

```php
Str::of('http://www.php.net/index.html')->capture('@^(?:http://)?(?P<host>[^/]+)@i')->equals(
    Map::of('scalar', Str::class)
        (0, Str::of('http://www.php.net'))
        (1, Str::of('www.php.net'))
        ('host', Str::of('www.php.net')),
);
```

## `->pregReplace()`

Replace part of the string by using a regular expression.

```php
Str::of('April 15, 2003')->pregReplace('/(\w+) (\d+), (\d+)/i', '${1}1,$3')->equals(
    Str::of('April1,2003'),
);
```

## `->substring()`

Return part of the string.

```php
Str::of('foobar')->substring(3)->equals(Str::of('bar')); // true
Str::of('foobar')->substring(3, 1)->equals(Str::of('b')); // true
```

## `->take()`

Return a new string with only the n first characters.

```php
Str::of('foobar')->take(3)->equals(Str::of('foo')); // true
```

## `->takeEnd()`

Return a new string with only the n last characters.

```php
Str::of('foobar')->takeEnd(3)->equals(Str::of('bar')); // true
```

## `->drop()`

Return a new string without the n first characters.

```php
Str::of('foobar')->drop(3)->equals(Str::of('bar')); // true
```

## `->dropEnd()`

Return a new string without the n last characters.

```php
Str::of('foobar')->dropEnd(3)->equals(Str::of('foo')); // true
```

## `->sprintf()`

Return a formatted string.

```php
Str::of('%s %s')->sprintf('hello', 'world')->equals(Str::of('hello world')); // true
```

## `->ucfirst()`

Return the string with the first letter as uppercase.

```php
Str::of('foobar')->ucfirst()->equals(Str::of('Foobar'));
```

## `->camelize()`

Return a CamelCase representation of the string.

```php
Str::of('foo bar_baz')->camelize()->equals(Str::of('fooBarBaz'));
```

**Note**: this function doesn't provide a true CamelCase as the first letter is lower cased. This will stay this way until the next major release to prevent breaking existing code relying on this bug.

## `->append()`

Append a string at the end of the current one.

```php
Str::of('foo')->append('bar')->equals(Str::of('foobar')); // true
```

## `->prepend()`

Prepend a string at the beginning of the current one.

```php
Str::of('foo')->prepend('bar')->equals(Str::of('barfoo')); // true
```

## `->equals()`

Check if the 2 strings are equal.

```php
Str::of('foo')->equals(Str::of('foo')); // true
Str::of('foo')->equals(Str::of('foo', 'UTF-8')); // true
Str::of('foo')->equals(Str::of('bar')); // false
```

## `->trim()`

Remove whitespace characters from both ends of the string.

```php
Str::of('  foo ')->trim()->equals(Str::of('foo')); // true
```

## `->contains()`

Check if the string contains another string.

```php
Str::of('foobar')->contains('ob'); // true
Str::of('foobar')->contains('baz'); // false
```

## `->startsWith()`

Check if the current string starts with the given string.

```php
Str::of('foobar')->startsWith('foo'); // true
Str::of('foobar')->startsWith('bar'); // false
```

## `->endsWith()`

Check if the current string ends with the given string.

```php
Str::of('foobar')->endsWith('bar'); // true
Str::of('foobar')->endsWith('foo'); // false
```
