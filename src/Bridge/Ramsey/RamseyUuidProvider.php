<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Bridge\Ramsey;

use DateTimeImmutable;
use PhpArchitecture\Uuid\Bridge\PredefinedProviderInterface;
use PhpArchitecture\Uuid\Provider\UuidProvider;
use Psr\Clock\ClockInterface;

final class RamseyUuidProvider extends UuidProvider implements PredefinedProviderInterface
{
    private \Ramsey\Uuid\UuidFactory $factory;

    public function __construct(?\Ramsey\Uuid\UuidFactory $factory = null)
    {
        $this->factory = $factory ?? self::getGlobalFactory();
    }

    private static function getGlobalFactory(): \Ramsey\Uuid\UuidFactory
    {
        $factory = \Ramsey\Uuid\Uuid::getFactory();
        assert($factory instanceof \Ramsey\Uuid\UuidFactory);

        return $factory;
    }

    public static function canInstantiate(): bool
    {
        return class_exists(\Ramsey\Uuid\UuidFactory::class, true) && class_exists(\Ramsey\Uuid\FeatureSet::class, true);
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
        $node = null;
        if ($nodeIdentifier !== null) {
            $node = strlen($nodeIdentifier) === 6 ? bin2hex($nodeIdentifier) : $nodeIdentifier;
        }

        if ($clock !== null) {
            return $this->createWithCustomClock($clock)->uuid1($node, $clockSequence)->toString();
        }

        return $this->factory->uuid1($node, $clockSequence)->toString();
    }

    public function v3(
        string $namespaceIdentifier,
        string $uniqueName
    ): string {
        return $this->factory->uuid3($namespaceIdentifier, $uniqueName)->toString();
    }

    public function v4(): string
    {
        return $this->factory->uuid4()->toString();
    }

    public function v5(
        string $namespaceIdentifier,
        string $uniqueName
    ): string {
        return $this->factory->uuid5($namespaceIdentifier, $uniqueName)->toString();
    }

    public function v6(
        ?ClockInterface $clock = null,
        ?int $clockSequence = null,
        ?string $nodeIdentifier = null
    ): string {
        $node = null;
        if ($nodeIdentifier !== null) {
            $hex = strlen($nodeIdentifier) === 6 ? bin2hex($nodeIdentifier) : $nodeIdentifier;
            $node = new \Ramsey\Uuid\Type\Hexadecimal($hex);
        }

        if ($clock !== null) {
            return $this->createWithCustomClock($clock)->uuid6($node, $clockSequence)->toString();
        }

        return $this->factory->uuid6($node, $clockSequence)->toString();
    }

    public function v7(
        ?ClockInterface $clock = null
    ): string {
        return $this->factory->uuid7($clock?->now() ?? new DateTimeImmutable())->toString();
    }

    public function v8(
        string $customData
    ): string {
        return $this->factory->uuid8($customData)->toString();
    }

    public function validate(
        string $uuid
    ): bool {
        return $this->factory->getValidator()->validate($uuid);
    }

    private function createWithCustomClock(ClockInterface $clock): \Ramsey\Uuid\UuidFactory
    {
        $dateTime = $clock->now();
        $timeProvider = new \Ramsey\Uuid\Provider\Time\FixedTimeProvider(
            new \Ramsey\Uuid\Type\Time($dateTime->format('U'), $dateTime->format('u')),
        );

        $featureSet = new \Ramsey\Uuid\FeatureSet();
        $featureSet->setTimeProvider($timeProvider);

        return new \Ramsey\Uuid\UuidFactory($featureSet);
    }

    public static function supportMatrix(): array
    {
        return [
            'v1' => 0.9,
            'v3' => 1.0,
            'v4' => 1.0,
            'v5' => 1.0,
            'v6' => 0.9,
            'v7' => 1.0,
            'v8' => 1.0,
            'validate' => 1.0,
        ];
    }
}
