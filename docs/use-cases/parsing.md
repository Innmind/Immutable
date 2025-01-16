# Parsing strings

This example will show how to parse a string without relying on exceptions to handle errors.

This is a simple case to parse the simplest form of a media type (ie `topLevel/subType`)

```php
use Innmind\Immutable\{
    Str,
    Maybe,
};

final class MediaType
{
    /**
     * @return Maybe<self>
     */
    public static function of(string $topLevel, string $subType): Maybe
    {
        if (/* $topLevel is not a valid one */) {
            /** @var Maybe<self> */
            return Maybe::nothing();
        }

        return Maybe::just(new self($topLevel, $subType));
    }
}

/** @var callable(string): Maybe<MediaType> $parse */
$parse = function(string $string): Maybe {
    // the regex only validate the form, it doesn't check the top level is a correct one
    $components = Str::of($string)->capture('~(?<topLevel>[a-z]+)/(?<subType>[a-z\-]+)~');

    return Maybe::all($components->get('topLevel'), $components->get('subType'))
        ->flatMap(fn(Str $topLevel, Str $subType) => MediaType::of(
            $topLevel->toString(),
            $subType->toString(),
        ));
}

$parse('application/json'); // Maybe::just(new MediaType('application', 'json'))
$parse(''); // Maybe::nothing() because no top level nor sub type
$parse('application/'); // Maybe::nothing() because no sub type
$parse('/json'); // Maybe::nothing() because no top level
$parse('unknown/json'); // Maybe::nothing() because top level is not valid
```
