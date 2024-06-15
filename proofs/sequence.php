<?php
declare(strict_types = 1);

use Innmind\Immutable\Sequence;

return static function() {
    yield test(
        'Sequence::toIdentity()',
        static function($assert) {
            $sequence = Sequence::of();

            $assert->same(
                $sequence,
                $sequence->toIdentity()->unwrap(),
            );
        },
    );
};
