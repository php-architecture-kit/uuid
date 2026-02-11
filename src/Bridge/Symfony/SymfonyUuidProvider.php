<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Bridge\Symfony;

use PhpArchitecture\Uuid\Bridge\PredefinedProviderInterface;
use PhpArchitecture\Uuid\Exception\ArgumentNotSupportedByProviderException;
use PhpArchitecture\Uuid\Exception\NotSupportedUuidVersionByProviderException;
use PhpArchitecture\Uuid\Provider\UuidProvider;
use Psr\Clock\ClockInterface;

final class SymfonyUuidProvider extends UuidProvider implements PredefinedProviderInterface
{
    public static function canInstantiate(): bool
    {
        return class_exists(\Symfony\Component\Uid\Uuid::class, true);
    }

    public static function newInstance(): self
    {
        return new self();
    }

    public function v1(
        ?ClockInterface $clock = null,
        ?int $clockSequence = null,
        ?string $nodeIdentifier = null
    ): string {
        if ($clockSequence !== null) {
            throw new ArgumentNotSupportedByProviderException('`symfony/uid` does not support clock sequence as argument for Uuid V1 - use another provider or do not pass clock sequence as argument');
        }

        $dateTime = $clock?->now() ?? new \DateTimeImmutable();
        
        $node = null;
        if ($nodeIdentifier !== null) {
            $nodeHex = strlen($nodeIdentifier) === 6 ? bin2hex($nodeIdentifier) : $nodeIdentifier;
            $pseudoUuid = '00000000-0000-4000-8000-' . $nodeHex;
            $node = \Symfony\Component\Uid\Uuid::fromString($pseudoUuid);
        }

        return \Symfony\Component\Uid\UuidV1::generate($dateTime, $node);
    }

    public function v3(
        string $namespaceIdentifier,
        string $uniqueName
    ): string {
        return \Symfony\Component\Uid\Uuid::v3(
            new \Symfony\Component\Uid\Uuid($namespaceIdentifier, false),
            $uniqueName,
        )->toRfc4122();
    }

    public function v4(): string
    {
        return \Symfony\Component\Uid\Uuid::v4()->toRfc4122();
    }

    public function v5(
        string $namespaceIdentifier,
        string $uniqueName
    ): string {
        return \Symfony\Component\Uid\Uuid::v5(
            new \Symfony\Component\Uid\Uuid($namespaceIdentifier, false),
            $uniqueName,
        )->toRfc4122();
    }

    public function v6(
        ?ClockInterface $clock = null,
        ?int $clockSequence = null,
        ?string $nodeIdentifier = null
    ): string {
        if ($clockSequence !== null) {
            throw new ArgumentNotSupportedByProviderException('`symfony/uid` does not support clock sequence as argument for Uuid V6 - use another provider or do not pass clock sequence as argument');
        }

        $dateTime = $clock?->now() ?? new \DateTimeImmutable();
        
        $node = null;
        if ($nodeIdentifier !== null) {
            $nodeHex = strlen($nodeIdentifier) === 6 ? bin2hex($nodeIdentifier) : $nodeIdentifier;
            $pseudoUuid = '00000000-0000-4000-8000-' . $nodeHex;
            $node = \Symfony\Component\Uid\Uuid::fromString($pseudoUuid);
        }

        return \Symfony\Component\Uid\UuidV6::generate($dateTime, $node);
    }

    public function v7(
        ?ClockInterface $clock = null
    ): string {
        $dateTime = $clock?->now() ?? new \DateTimeImmutable();

        return \Symfony\Component\Uid\UuidV7::generate($dateTime);
    }

    public function v8(
        string $customData
    ): string {
        throw new NotSupportedUuidVersionByProviderException('`symfony/uid` does not support Uuid V8 in a expected way');
    }

    public function validate(
        string $uuid
    ): bool {
        return \Symfony\Component\Uid\Uuid::isValid($uuid);
    }

    public static function supportMatrix(): array
    {
        return [
            'v1' => class_exists(\Symfony\Component\Uid\UuidV1::class, false) ? 0.66 : 0.0,
            'v3' => 1.0,
            'v4' => 1.0,
            'v5' => 1.0,
            'v6' => class_exists(\Symfony\Component\Uid\UuidV6::class, false) ? 0.66 : 0.0,
            'v7' => class_exists(\Symfony\Component\Uid\UuidV7::class, false) ? 1.0 : 0.0,
            'v8' => 0.0,
            'validate' => 1.0,
        ];
    }
}
