<?php
declare(strict_types = 1);

use Innmind\Immutable\Predicate\Instance;

return static function() {
    yield test(
        'Or predicate',
        static function($assert) {
            $array = new \SplFixedArray;

            $assert->true(
                Instance::of(\Countable::class)
                    ->or(Instance::of(\stdClass::class))
                    ($array),
            );
            $assert->true(
                Instance::of(\stdClass::class)
                    ->or(Instance::of(\Countable::class))
                    ($array),
            );
            $assert->false(
                Instance::of(\Throwable::class)
                    ->or(Instance::of(\Unknown::class))
                    ($array),
            );
        },
    );

    yield test(
        'And predicate',
        static function($assert) {
            $array = new \SplFixedArray;

            $assert->true(
                Instance::of(\Countable::class)
                    ->and(Instance::of(\Traversable::class))
                    ($array),
            );
            $assert->false(
                Instance::of(\Throwable::class)
                    ->and(Instance::of(\Countable::class))
                    ($array),
            );
            $assert->false(
                Instance::of(\Countable::class)
                    ->and(Instance::of(\Throwable::class))
                    ($array),
            );
        },
    );
};
