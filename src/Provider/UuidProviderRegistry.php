<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Provider;

use PhpArchitecture\Uuid\Bridge\Ramsey\RamseyUuidProvider;
use PhpArchitecture\Uuid\Bridge\Symfony\SymfonyUuidProvider;
use PhpArchitecture\Uuid\Bridge\PredefinedProviderInterface;
use PhpArchitecture\Uuid\Exception\MissingProviderException;

final class UuidProviderRegistry
{
    /**
     * @var array<class-string<UuidProvider&PredefinedProviderInterface>>
     */
    private const PREDEFINED_PROVIDERS = [
        SymfonyUuidProvider::class,
        RamseyUuidProvider::class
    ];

    /**
     * @var array<string,UuidProvider>
     */
    private static array $instances = [];

    /**
     * @var array<string,string> method => provider name
     */
    private static array $bestProvidersBySupport = [];
    private static bool $predefinedProvidersRegistered = false;

    public static function registerPredefinedProviders(): void
    {
        foreach (self::PREDEFINED_PROVIDERS as $providerClass) {
            if ($providerClass::canInstantiate()) {
                self::register($providerClass, $providerClass::newInstance());
            }
        }

        self::$predefinedProvidersRegistered = true;
    }

    public static function getBestProviderForMethod(string $method): UuidProvider
    {
        if (!isset(self::$bestProvidersBySupport[$method]) && !self::$predefinedProvidersRegistered) {
            self::registerPredefinedProviders();
        }

        if (!isset(self::$bestProvidersBySupport[$method])) {
            throw new MissingProviderException("Provider for method $method is not registered");
        }

        return self::$instances[self::$bestProvidersBySupport[$method]];
    }

    public static function get(string $name): UuidProvider
    {
        if (!self::has($name) && !self::$predefinedProvidersRegistered) {
            self::registerPredefinedProviders();
        }

        if (!self::has($name)) {
            throw new MissingProviderException("Provider $name is not registered");
        }

        return self::$instances[$name];
    }

    public static function has(string $name): bool
    {
        return isset(self::$instances[$name]);
    }

    public static function register(string $name, UuidProvider $provider): void
    {
        self::$instances[$name] = $provider;
        $supportMatrix = $provider::supportMatrix();

        foreach ($supportMatrix as $method => $supportScore) {
            if (!isset(self::$bestProvidersBySupport[$method])) {
                self::$bestProvidersBySupport[$method] = $name;

                continue;
            }

            $currentBestProvider = self::$instances[self::$bestProvidersBySupport[$method]];
            $currentBestScore = $currentBestProvider::supportMatrix()[$method];

            if ($supportScore > $currentBestScore) {
                self::$bestProvidersBySupport[$method] = $name;
            }
        }
    }

    public static function reset(): void
    {
        self::$instances = [];
        self::$bestProvidersBySupport = [];
        self::$predefinedProvidersRegistered = false;
    }
}
