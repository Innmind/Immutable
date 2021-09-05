# How to read a file

This example below will show you how to build complex pipelines to read files without ever loading the whole file in memory, allowing you to read any file's size.

This example below looks for a user in a csv file with a score above `100`.

```php
use Innmind\Immutable\{
    Sequence,
    Str,
};

$lines = Sequence::lazy(function(callable $registerCleanup) {
    $handle = \fopen('users.csv', 'r');
    $registerCleanup(fn() => \fclose($handle));

    while (!\feof($handle)) {
        yield (string) \fgets($handle);
    }

    \fclose($handle);
});
/** @var Maybe<User> */
$user = $lines
    ->map(fn(string $line) => Str::of($line))
    ->map(fn(Str $line) => $line->trim())
    ->filter(fn(Str $line) => !$line->empty())
    ->filter(fn(Str $line) => $line->contains(','))
    ->map(fn(Str $line) => $line->split(','))
    ->map(fn(Sequence $columns) => User::of($columns)) // ficticious class
    ->find(fn(User $user) => $user->score() > 100);
```

The final `$user` variable is an instance of `Maybe` because the user may or may not exist.

If a user is found before the end of the file the sequence will stop reading the file and call the function passed to `$registerCleanup` allowing you to close the file handle properly.

Since this is a lazy sequence the file is iterated over only when trying to extract a concrete value, in this case via `->find()`. This means that even though the pipeline contains multiple steps the file is read only once. This decoupling between reading the file and building a pipeline to compute a value allows you to split the construction of the pipeline across multiple layers in your application without worrying about performance.

The other advantage of this technique is that it allows to read files that may not fit in memory.

## Merging multiple files in a single pipeline

The lazyness described above still works when you combine multiple lazy sequences.

```php
$openFile = fn(string $name) => function(callable $registerCleanup) use ($name) {
    $handle = \fopen($name, 'r');
    $registerCleanup(fn() => \fclose($handle));

    while (!\feof($handle)) {
        yield (string) \fgets($handle);
    }

    \fclose($handle);
};

$users = Sequence::lazy($openFile('users1.csv'))
    ->append(Sequence::lazy($openFile('users2.csv')))
    ->append(Sequence::lazy($openFile('users3.csv')));

/** @var callable(Sequence<string>): Maybe<User> $findUser */
$findUser = function(Sequence $users): Maybe {
    // todo build a pipeline to find a user
};

$user = $findUser($users);
```

Here we create a sequence that will sequentially read the 3 files `users1.csv`, `users2.csv` and `users3.csv` but the `$findUser` function is not aware of where the data comes from. This composition will open `users2.csv` only if the user is not found in `users1.csv`.

In this example the 3 sources are all files but you could mix the sources with different generators, for example you could combine from a file with another one coming from a database.
