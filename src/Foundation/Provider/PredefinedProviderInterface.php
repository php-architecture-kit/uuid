<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Foundation\Provider;

interface PredefinedProviderInterface
{
    public static function canInstantiate(): bool;

    public static function newInstance(): self;
}
