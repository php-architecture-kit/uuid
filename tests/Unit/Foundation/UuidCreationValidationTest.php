<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Tests\Unit\Foundation;

use PhpArchitecture\Uuid\Foundation\Exception\InvalidUuidCreationArgumentException;
use PhpArchitecture\Uuid\Foundation\Uuid;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UuidCreationValidationTest extends TestCase
{
    #[Test]
    public function v1ThrowsExceptionWhenClockSequenceIsNegative(): void
    {
        $this->expectException(InvalidUuidCreationArgumentException::class);
        $this->expectExceptionMessage('Clock sequence must be between 0 and 16383');

        Uuid::v1(null, -1);
    }

    #[Test]
    public function v1ThrowsExceptionWhenClockSequenceExceedsMax(): void
    {
        $this->expectException(InvalidUuidCreationArgumentException::class);
        $this->expectExceptionMessage('Clock sequence must be between 0 and 16383');

        Uuid::v1(null, 16384);
    }

    #[Test]
    public function v1AcceptsValidClockSequenceRange(): void
    {
        $uuid0 = Uuid::v1(null, 0);
        $uuidMax = Uuid::v1(null, 16383);

        $this->assertInstanceOf(Uuid::class, $uuid0);
        $this->assertInstanceOf(Uuid::class, $uuidMax);
    }

    #[Test]
    public function v1ThrowsExceptionForInvalidNodeIdentifierLength(): void
    {
        $this->expectException(InvalidUuidCreationArgumentException::class);
        $this->expectExceptionMessage('Node identifier must be 6 bytes or 12 hex characters');

        Uuid::v1(null, null, 'abc'); // too short
    }

    #[Test]
    public function v1Accepts6ByteNodeIdentifier(): void
    {
        $uuid = Uuid::v1(null, null, "\x01\x02\x03\x04\x05\x06");

        $this->assertInstanceOf(Uuid::class, $uuid);
    }

    #[Test]
    public function v1Accepts12HexCharNodeIdentifier(): void
    {
        $uuid = Uuid::v1(null, null, '010203040506');

        $this->assertInstanceOf(Uuid::class, $uuid);
    }

    #[Test]
    public function v6ThrowsExceptionWhenClockSequenceIsNegative(): void
    {
        $this->expectException(InvalidUuidCreationArgumentException::class);
        $this->expectExceptionMessage('Clock sequence must be between 0 and 16383');

        Uuid::v6(null, -1);
    }

    #[Test]
    public function v6ThrowsExceptionWhenClockSequenceExceedsMax(): void
    {
        $this->expectException(InvalidUuidCreationArgumentException::class);
        $this->expectExceptionMessage('Clock sequence must be between 0 and 16383');

        Uuid::v6(null, 16384);
    }

    #[Test]
    public function v6AcceptsValidClockSequenceRange(): void
    {
        $uuid0 = Uuid::v6(null, 0);
        $uuidMax = Uuid::v6(null, 16383);

        $this->assertInstanceOf(Uuid::class, $uuid0);
        $this->assertInstanceOf(Uuid::class, $uuidMax);
    }

    #[Test]
    public function v6ThrowsExceptionForInvalidNodeIdentifierLength(): void
    {
        $this->expectException(InvalidUuidCreationArgumentException::class);
        $this->expectExceptionMessage('Node identifier must be 6 bytes or 12 hex characters');

        Uuid::v6(null, null, 'toolong1234567'); // 14 chars - invalid
    }

    #[Test]
    public function v6Accepts6ByteNodeIdentifier(): void
    {
        $uuid = Uuid::v6(null, null, "\x01\x02\x03\x04\x05\x06");

        $this->assertInstanceOf(Uuid::class, $uuid);
    }

    #[Test]
    public function v6Accepts12HexCharNodeIdentifier(): void
    {
        $uuid = Uuid::v6(null, null, 'aabbccddeeff');

        $this->assertInstanceOf(Uuid::class, $uuid);
    }

    #[Test]
    public function v8ThrowsExceptionWhenCustomDataIsNot16Bytes(): void
    {
        $this->expectException(InvalidUuidCreationArgumentException::class);
        $this->expectExceptionMessage('Custom data must be exactly 16 bytes');

        Uuid::v8('short');
    }

    #[Test]
    public function v8ThrowsExceptionWhenCustomDataIsTooLong(): void
    {
        $this->expectException(InvalidUuidCreationArgumentException::class);
        $this->expectExceptionMessage('Custom data must be exactly 16 bytes');

        Uuid::v8(str_repeat("\x00", 17));
    }

    #[Test]
    public function v8AcceptsExactly16Bytes(): void
    {
        $customData = "\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0a\x0b\x0c\x0d\x0e\x0f\x10";
        $uuid = Uuid::v8($customData);

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame(8, $uuid->getVersion());
    }
}
