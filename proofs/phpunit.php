<?php
declare(strict_types = 1);

use Innmind\BlackBox\PHPUnit\Load;

return static function() {
    yield from Load::testsAt(__DIR__.'/../tests/');
};
