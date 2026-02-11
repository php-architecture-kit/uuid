<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Bridge;

interface PredefinedProviderInterface
{
    public static function canInstantiate(): bool;

    public static function newInstance(): self;
}
