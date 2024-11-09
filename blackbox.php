<?php
declare(strict_types = 1);

require 'vendor/autoload.php';

use Innmind\BlackBox\{
    Application,
    Runner\Load,
    Runner\CodeCoverage,
};

Application::new($argv)
    ->when(
        \getenv('ENABLE_COVERAGE') !== false,
        static fn(Application $app) => $app
            ->codeCoverage(
                CodeCoverage::of(
                    __DIR__.'/src/',
                    __DIR__.'/proofs/',
                    __DIR__.'/fixtures/',
                )
                    ->dumpTo('coverage.clover')
                    ->enableWhen(true),
            )
            ->scenariiPerProof(1),
    )
    ->when(
        \getenv('CI') !== false,
        static fn(Application $app) => $app->scenariiPerProof(1_000),
    )
    ->tryToProve(Load::everythingIn(__DIR__.'/proofs/'))
    ->exit();
