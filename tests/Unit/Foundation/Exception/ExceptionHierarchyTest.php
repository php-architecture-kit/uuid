<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Tests\Unit\Foundation\Exception;

use PhpArchitecture\Uuid\Foundation\Exception\ArgumentNotSupportedByProviderException;
use PhpArchitecture\Uuid\Foundation\Exception\InvalidUuidCreationArgumentException;
use PhpArchitecture\Uuid\Foundation\Exception\InvalidUuidException;
use PhpArchitecture\Uuid\Foundation\Exception\MissingProviderException;
use PhpArchitecture\Uuid\Foundation\Exception\NotSupportedUuidVersionByProviderException;
use PhpArchitecture\Uuid\Foundation\Exception\UuidException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;
use RuntimeException;

class ExceptionHierarchyTest extends TestCase
{
    #[Test]
    public function invalidUuidExceptionExtendsRuntimeException(): void
    {
        $exception = new InvalidUuidException('test');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    #[Test]
    public function invalidUuidExceptionImplementsUuidException(): void
    {
        $exception = new InvalidUuidException('test');

        $this->assertInstanceOf(UuidException::class, $exception);
    }

    #[Test]
    public function invalidUuidCreationArgumentExceptionExtendsInvalidArgumentException(): void
    {
        $exception = new InvalidUuidCreationArgumentException('test');

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    }

    #[Test]
    public function invalidUuidCreationArgumentExceptionImplementsUuidException(): void
    {
        $exception = new InvalidUuidCreationArgumentException('test');

        $this->assertInstanceOf(UuidException::class, $exception);
    }

    #[Test]
    public function missingProviderExceptionImplementsUuidException(): void
    {
        $exception = new MissingProviderException('test');

        $this->assertInstanceOf(UuidException::class, $exception);
    }

    #[Test]
    public function argumentNotSupportedByProviderExceptionImplementsUuidException(): void
    {
        $exception = new ArgumentNotSupportedByProviderException('test');

        $this->assertInstanceOf(UuidException::class, $exception);
    }

    #[Test]
    public function notSupportedUuidVersionByProviderExceptionImplementsUuidException(): void
    {
        $exception = new NotSupportedUuidVersionByProviderException('test');

        $this->assertInstanceOf(UuidException::class, $exception);
    }

    #[Test]
    public function allExceptionsHaveCorrectMessage(): void
    {
        $message = 'Custom error message';

        $this->assertSame($message, (new InvalidUuidException($message))->getMessage());
        $this->assertSame($message, (new InvalidUuidCreationArgumentException($message))->getMessage());
        $this->assertSame($message, (new MissingProviderException($message))->getMessage());
        $this->assertSame($message, (new ArgumentNotSupportedByProviderException($message))->getMessage());
        $this->assertSame($message, (new NotSupportedUuidVersionByProviderException($message))->getMessage());
    }
}
