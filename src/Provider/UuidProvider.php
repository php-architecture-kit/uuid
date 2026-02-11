<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Provider;

use Psr\Clock\ClockInterface;

abstract class UuidProvider
{
    abstract public function v1(
        ?ClockInterface $clock = null,
        ?int $clockSequence = null,
        ?string $nodeIdentifier = null
    ): string;

    abstract public function v3(
        string $namespaceIdentifier,
        string $uniqueName
    ): string;

    abstract public function v4(): string;

    abstract public function v5(
        string $namespaceIdentifier,
        string $uniqueName
    ): string;

    abstract public function v6(
        ?ClockInterface $clock = null,
        ?int $clockSequence = null,
        ?string $nodeIdentifier = null
    ): string;

    abstract public function v7(
        ?ClockInterface $clock = null
    ): string;

    abstract public function v8(
        string $customData
    ): string;

    abstract public function validate(
        string $uuid
    ): bool;

    /**
     * Returns the support matrix for each UUID version.
     * The value is a float between 0.0 and 1.0, where 1.0 means full support and 0.0 means no support.
     *
     * @return array{"v1":float,"v3":float,"v4":float,"v5":float,"v6":float,"v7":float,"v8":float,"validate":float}
     */
    public static function supportMatrix(): array
    {
        return [
            'v1' => 0.0,
            'v3' => 0.0,
            'v4' => 0.0,
            'v5' => 0.0,
            'v6' => 0.0,
            'v7' => 0.0,
            'v8' => 0.0,
            'validate' => 0.0,
        ];
    }
}
