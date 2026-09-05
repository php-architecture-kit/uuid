<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/../../benchmarks',
        __DIR__ . '/../../src',
        __DIR__ . '/../../tests',
        __DIR__ . '/../../tools',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets(php80: true)
    ->withTypeCoverageLevel(75)
    ->withDeadCodeLevel(74)
    ->withCodeQualityLevel(79);
