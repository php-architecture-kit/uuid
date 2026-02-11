<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid;

use PhpArchitecture\Uuid\Exception\InvalidUuidCreationArgumentException;
use PhpArchitecture\Uuid\Exception\InvalidUuidException;
use PhpArchitecture\Uuid\Provider\UuidProviderRegistry;
use Psr\Clock\ClockInterface;
use Stringable;

class Uuid implements \Stringable
{
    public const NAMESPACE_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    private string $value;

    /**
     * Creates UUID from binary representation (16 bytes).
     * 
     * @param string $bytes 16-byte binary string
     * @param bool $validate Whether to validate the UUID format
     * 
     * @return static
     * @throws InvalidUuidException If bytes are not exactly 16 bytes or invalid UUID
     */
    final public static function fromBinary(string $bytes, bool $validate = true): static
    {
        if (strlen($bytes) !== 16) {
            throw new InvalidUuidException('UUID bytes must be exactly 16 bytes, got ' . strlen($bytes));
        }

        $hex = bin2hex($bytes);

        $uuid = sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20),
        );

        return static::fromString($uuid, $validate);
    }

    /**
     * Creates UUID from string representation.
     * 
     * Use validate=false only when:
     * - Loading from trusted storage (e.g., database where format is guaranteed)
     * - You've already validated the UUID externally
     * - Performance-critical scenarios with pre-validated data
     * 
     * @param string $value UUID string in canonical format (e.g., '123e4567-e89b-12d3-a456-426614174000')
     * @param bool $validate Whether to validate UUID format. Default: true (recommended for external input)
     * 
     * @return static
     * @throws InvalidUuidException If validation is enabled and UUID format is invalid
     */
    final public static function fromString(string $value, bool $validate = true): static
    {
        if ($validate && !UuidProviderRegistry::getBestProviderForMethod('validate')->validate($value)) {
            throw new InvalidUuidException("Invalid UUID: $value");
        }

        return new static($value);
    }

    /**
     * Simple factory method for generating a new UUID.
     * 
     * This is the recommended way to create a UUID when you simply need a unique identifier
     * without specific version requirements. Currently returns UUID v7 (time-ordered, recommended).
     * 
     * This method is non-final and can be overridden in your domain-specific {Entity}Id classes
     * to customize UUID generation according to your needs (e.g., different version, custom logic).
     * 
     * Example override:
     * ```php
     * class UserId extends Uuid
     * {
     *     public static function new(): self
     *     {
     *         return self::v4(); // Use random UUID for user IDs
     *     }
     * }
     * ```
     * 
     * @return static
     */
    public static function new(): static
    {
        return static::v7();
    }

    /**
     * Creates a time-based UUID. Each generated UUID contains a timestamp,
     * allowing natural chronological sorting.
     * 
     * To avoid collisions between UUIDs generated at the same moment (or on different machines),
     * UUID v1 uses two additional components:
     * - **Clock sequence** - a counter that distinguishes UUIDs created in the same microsecond
     *   or when the system clock was rolled back. In practice: a randomly generated value at process start.
     * - **Node identifier** - a machine/process identifier that differentiates UUIDs from different sources.
     *   Historically this was a MAC address, nowadays random bytes are preferred for security.
     * 
     * In most cases, calling `Uuid::v1()` without parameters is sufficient - everything
     * will be properly randomized or the system clock will be used.
     * 
     * @see https://www.rfc-editor.org/rfc/rfc9562.html#name-uuid-version-1
     * 
     * @param ?ClockInterface $clock Custom clock implementation (e.g., for testing). Null = system clock
     * @param ?int $clockSequence Custom session counter (0-16383). Null = random value
     * @param ?string $nodeIdentifier Custom node identifier (6 bytes binary or 12 hex chars). Null = random bytes
     * 
     * @return static
     * 
     * @throws InvalidUuidCreationArgumentException When invalid parameters are provided
     */
    final public static function v1(
        ?ClockInterface $clock = null,
        ?int $clockSequence = null,
        ?string $nodeIdentifier = null,
    ): static {
        if ($clockSequence !== null && ($clockSequence < 0 || $clockSequence > 16383)) {
            throw new InvalidUuidCreationArgumentException('Clock sequence must be between 0 and 16383');
        }

        if ($nodeIdentifier !== null && strlen($nodeIdentifier) !== 6 && strlen($nodeIdentifier) !== 12) {
            throw new InvalidUuidCreationArgumentException('Node identifier must be 6 bytes or 12 hex characters');
        }

        return new static(UuidProviderRegistry::getBestProviderForMethod('v1')->v1($clock, $clockSequence, $nodeIdentifier));
    }

    /**
     * Creates a deterministic UUID based on a namespace and a name.
     * The same namespace + name combination always produces the same UUID.
     * 
     * This is useful when you need reproducible UUIDs from existing data (like URLs, domain names,
     * or any identifiers). Since the result is deterministic, you can safely regenerate the UUID
     * anytime without storing it - perfect for caching keys, content addressing, or data deduplication.
     * 
     * The namespace acts as a "context" or "category" for your names. Common predefined namespaces:
     * - DNS: `6ba7b810-9dad-11d1-80b4-00c04fd430c8` (for domain names like "example.com")
     * - URL: `6ba7b811-9dad-11d1-80b4-00c04fd430c8` (for URLs like "https://example.com/page")
     * - OID: `6ba7b812-9dad-11d1-80b4-00c04fd430c8` (for ISO OID strings)
     * - X500: `6ba7b814-9dad-11d1-80b4-00c04fd430c8` (for X.500 Distinguished Names)
     * 
     * You can also use your own custom namespace UUID to create application-specific identifiers.
     * 
     * @see https://www.rfc-editor.org/rfc/rfc9562.html#name-uuid-version-3
     * 
     * @param self $namespaceIdentifier Namespace UUID defining the context
     * @param string $uniqueName A name unique within the given namespace
     * 
     * @return static
     */
    final public static function v3(
        self $namespaceIdentifier,
        string $uniqueName,
    ): static {
        return new static(UuidProviderRegistry::getBestProviderForMethod('v3')->v3($namespaceIdentifier->value(), $uniqueName));
    }

    /**
     * Creates a random UUID. This is the most commonly used UUID version.
     * 
     * Each UUID v4 is generated from 122 random bits, providing astronomically low
     * collision probability. Unlike v1, it contains no timestamp or machine information,
     * making it privacy-friendly and suitable for public identifiers.
     * 
     * Use v4 when you simply need a unique identifier without any special properties
     * (like time-ordering or determinism). It's the safest default choice for most applications:
     * database primary keys, session IDs, file names, API request IDs, etc.
     * 
     * @see https://www.rfc-editor.org/rfc/rfc9562.html#name-uuid-version-4
     * 
     * @return static
     */
    final public static function v4(): static
    {
        return new static(UuidProviderRegistry::getBestProviderForMethod('v4')->v4());
    }

    /**
     * Creates a deterministic UUID based on a namespace and a name, using SHA-1 hashing.
     * This is the recommended version for namespace-based UUIDs (improved version of v3).
     * 
     * Works exactly like v3, but uses SHA-1 instead of MD5, making it more robust
     * and future-proof. The same namespace + name combination always produces the same UUID.
     * 
     * Prefer v5 over v3 for new projects - it provides better cryptographic properties
     * while maintaining the same deterministic behavior and use cases.
     * 
     * The namespace acts as a "context" or "category" for your names. Common predefined namespaces:
     * - DNS: `6ba7b810-9dad-11d1-80b4-00c04fd430c8` (for domain names like "example.com")
     * - URL: `6ba7b811-9dad-11d1-80b4-00c04fd430c8` (for URLs like "https://example.com/page")
     * - OID: `6ba7b812-9dad-11d1-80b4-00c04fd430c8` (for ISO OID strings)
     * - X500: `6ba7b814-9dad-11d1-80b4-00c04fd430c8` (for X.500 Distinguished Names)
     * 
     * @see https://www.rfc-editor.org/rfc/rfc9562.html#name-uuid-version-5
     * 
     * @param self $namespaceIdentifier Namespace UUID defining the context
     * @param string $uniqueName A name unique within the given namespace
     * 
     * @return static
     */
    final public static function v5(
        self $namespaceIdentifier,
        string $uniqueName,
    ): static {
        return new static(UuidProviderRegistry::getBestProviderForMethod('v5')->v5($namespaceIdentifier->value(), $uniqueName));
    }

    /**
     * Creates a time-based UUID with improved sorting properties (reordered v1).
     * 
     * Like v1, each UUID contains a timestamp, but v6 reorganizes the timestamp bits
     * from most-significant to least-significant. This makes UUIDs naturally sortable
     * by creation time - perfect for database primary keys and indexes where insertion
     * order matches chronological order.
     * 
     * Key improvements over v1:
     * - **Better database performance** - sequential nature reduces index fragmentation
     * - **Lexicographic sorting** - UUIDs sort correctly as strings without conversion
     * - **Same uniqueness guarantees** - still uses clock sequence and node identifier
     * 
     * Unlike v1, RFC recommends randomizing clock sequence and node identifier for each UUID
     * rather than keeping them constant per process. This provides better privacy and
     * collision resistance in distributed systems.
     * 
     * In most cases, call `Uuid::v6()` without parameters - both clock sequence and node
     * identifier will be randomly generated for each UUID as recommended.
     * 
     * @see https://www.rfc-editor.org/rfc/rfc9562.html#name-uuid-version-6
     * 
     * @param ?ClockInterface $clock Custom clock implementation (e.g., for testing). Null = system clock
     * @param ?int $clockSequence Custom counter (0-16383). Null = random per UUID (recommended)
     * @param ?string $nodeIdentifier Custom node identifier (6 bytes binary or 12 hex chars). Null = random per UUID (recommended)
     * 
     * @return static
     * 
     * @throws InvalidUuidCreationArgumentException When invalid parameters are provided
     */
    final public static function v6(
        ?ClockInterface $clock = null,
        ?int $clockSequence = null,
        ?string $nodeIdentifier = null,
    ): static {
        if ($clockSequence !== null && ($clockSequence < 0 || $clockSequence > 16383)) {
            throw new InvalidUuidCreationArgumentException('Clock sequence must be between 0 and 16383');
        }

        if ($nodeIdentifier !== null && strlen($nodeIdentifier) !== 6 && strlen($nodeIdentifier) !== 12) {
            throw new InvalidUuidCreationArgumentException('Node identifier must be 6 bytes or 12 hex characters');
        }

        return new static(UuidProviderRegistry::getBestProviderForMethod('v6')->v6($clock, $clockSequence, $nodeIdentifier));
    }

    /**
     * Creates a time-ordered UUID with random data. This is the newest and recommended version
     * for most applications that need sortable identifiers.
     * 
     * Combines the best of both worlds:
     * - **Unix timestamp** (millisecond precision) ensures chronological ordering
     * - **Random bits** provide uniqueness without exposing machine/process information
     * 
     * Unlike v1/v6, v7 doesn't use clock sequence or node identifier at all - everything
     * after the timestamp is random. This makes it:
     * - **Simpler** - no complex collision avoidance mechanisms needed
     * - **Privacy-friendly** - no machine-specific information leaked
     * - **Database-optimized** - sequential for indexes, random enough to avoid hotspots
     * 
     * Perfect for:
     * - Database primary keys where chronological ordering matters
     * - Distributed systems generating IDs independently
     * - Event sourcing and time-series data
     * - Any scenario where you'd use v4 but want time-based sorting
     * 
     * If you need sortable UUIDs, prefer v7 over v6 for new projects due to its simplicity
     * and better randomness properties.
     * 
     * @see https://www.rfc-editor.org/rfc/rfc9562.html#name-uuid-version-7
     * 
     * @param ?ClockInterface $clock Custom clock implementation (e.g., for testing). Null = system clock
     * 
     * @return static
     */
    final public static function v7(?ClockInterface $clock = null): static
    {
        return new static(UuidProviderRegistry::getBestProviderForMethod('v7')->v7($clock));
    }

    /**
     * Creates a custom/vendor-specific UUID. This version is reserved for experimental
     * or application-specific UUID formats that don't fit into other versions.
     * 
     * UUID v8 provides maximum flexibility - you define how the 122 bits of data are used,
     * while maintaining the standard UUID structure (version and variant bits are set automatically).
     * This is useful when:
     * - You need a custom encoding scheme for specific domain requirements
     * - Existing UUID versions don't match your use case
     * - You're experimenting with new UUID generation approaches
     * - You want to embed application-specific data in a UUID format
     * 
     * Common use cases:
     * - Embedding multiple IDs or codes into one UUID (e.g., tenant ID + entity ID)
     * - Custom time representations with different precision or epoch
     * - Domain-specific identifiers that need UUID compatibility
     * - Migrating from legacy ID systems while maintaining UUID format
     * 
     * **Important**: Since v8 is vendor-specific, ensure your custom format is well-documented
     * and understood by all systems that will interact with these UUIDs. Unlike other versions,
     * there's no standard interpretation of the data bits.
     * 
     * **Input format**: The $customData parameter must be a binary string (raw bytes), NOT a hex string.
     * Examples:
     * - Using hex escape sequences: "\x01\x02\x03..." (16 bytes)
     * - Convert from hex string: hex2bin("0102030405...") (32 hex chars → 16 bytes)
     * - Using pack(): pack('C*', 1, 2, 3, ..., 16)
     * - Random bytes: random_bytes(16)
     * 
     * @see https://www.rfc-editor.org/rfc/rfc9562.html#name-uuid-version-8
     * 
     * @param string $customData Binary string (16 bytes). Bits 48-51 (version) and 64-65 (variant) will be overwritten.
     * 
     * @return static
     * 
     * @throws InvalidUuidCreationArgumentException When custom data is not exactly 16 bytes
     */
    final public static function v8(string $customData): static
    {
        if (strlen($customData) !== 16) {
            throw new InvalidUuidCreationArgumentException(
                sprintf(
                    'Custom data must be exactly 16 bytes (binary string), got %d bytes. ' .
                        'Use hex2bin() to convert hex strings, or pack() for raw values.',
                    strlen($customData),
                ),
            );
        }

        return new static(UuidProviderRegistry::getBestProviderForMethod('v8')->v8($customData));
    }

    /**
     * Returns the Nil UUID (all zeros). This special UUID represents "no value" or absence of an identifier.
     * 
     * Useful as a sentinel value, default/placeholder, or to indicate an uninitialized state.
     * Always returns: `00000000-0000-0000-0000-000000000000`
     * 
     * @see https://www.rfc-editor.org/rfc/rfc9562.html#name-nil-uuid
     * 
     * @return static
     */
    final public static function nil(): static
    {
        return new static('00000000-0000-0000-0000-000000000000');
    }

    /**
     * Returns the Max UUID (all ones). This special UUID represents the highest possible UUID value.
     * 
     * Useful for range queries, upper bounds, or special sentinel values.
     * Always returns: `FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF`
     * 
     * @see https://www.rfc-editor.org/rfc/rfc9562.html#name-max-uuid
     * 
     * @return static
     */
    final public static function max(): static
    {
        return new static('FFFFFFFF-FFFF-FFFF-FFFF-FFFFFFFFFFFF');
    }

    final private function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Compares this UUID with another UUID or string for equality.
     * 
     * @param self|string $other UUID instance or string representation to compare
     * @return bool True if UUIDs are equal
     */
    final public function equals(self|string $other): bool
    {
        if ($other instanceof self) {
            return strcasecmp($this->value, $other->value()) === 0;
        }

        return strcasecmp($this->value, $other) === 0;
    }

    /**
     * Extracts clock sequence from UUID v1 or v6.
     * Returns null for other versions.
     * 
     * Clock sequence is 14 bits stored in clock_seq_hi_and_reserved and clock_seq_low.
     * 
     * @return int|null Value between 0 and 16383 (0x0000-0x3FFF)
     */
    final public function getClockSequence(): ?int
    {
        $version = $this->getVersion();

        if ($version === 0x1 || $version === 0x6) {
            $clockSeqHex = substr($this->value, 19, 4);
            $clockSeq = hexdec($clockSeqHex);

            return $clockSeq & 0x3FFF;
        }

        return null;
    }

    /**
     * Extracts node identifier from UUID v1 or v6.
     * Returns null for other versions.
     * 
     * Node is the last 48 bits (12 hex chars) of the UUID.
     * 
     * @return string|null 12-character hexadecimal string (without dashes)
     */
    final public function getNode(): ?string
    {
        $version = $this->getVersion();

        if ($version === 0x1 || $version === 0x6) {
            return substr($this->value, 24);
        }

        return null;
    }

    /**
     * Extracts timestamp from time-based UUID (v1, v6, v7).
     * Returns null for non-time-based versions.
     * 
     * @return \DateTimeImmutable|null
     */
    final public function getTime(): ?\DateTimeImmutable
    {
        $version = $this->getVersion();

        if ($version === 0x1) {
            $timeLow = hexdec(substr($this->value, 0, 8));
            $timeMid = hexdec(substr($this->value, 9, 4));
            $timeHi = hexdec(substr($this->value, 15, 3));

            $timestamp = ($timeHi << 48) | ($timeMid << 32) | $timeLow;

            $gregorianOffset = 122192928000000000;
            $unixTimestamp = ($timestamp - $gregorianOffset) / 10000000;

            return new \DateTimeImmutable('@' . (int)$unixTimestamp);
        }

        if ($version === 0x6) {
            $timeHigh = hexdec(substr($this->value, 0, 8) . substr($this->value, 9, 4));
            $timeLow = hexdec(substr($this->value, 15, 3));

            $timestamp = ($timeHigh << 12) | $timeLow;

            $gregorianOffset = 122192928000000000;
            $unixTimestamp = ($timestamp - $gregorianOffset) / 10000000;

            return new \DateTimeImmutable('@' . (int)$unixTimestamp);
        }

        if ($version === 0x7) {
            $timestampMs = hexdec(substr($this->value, 0, 8) . substr($this->value, 9, 4));

            $seconds = intdiv($timestampMs, 1000);
            $microseconds = ($timestampMs % 1000) * 1000;

            return \DateTimeImmutable::createFromFormat('U.u', sprintf('%d.%06d', $seconds, $microseconds));
        }

        return null;
    }

    /**
     * Extracts the UUID version from the version field.
     * 
     * The version is stored in the most significant 4 bits of the 7th octet (time_hi_and_version).
     * For string UUID format 'xxxxxxxx-xxxx-Mxxx-Nxxx-xxxxxxxxxxxx', this is the character at position 14
     * (where M contains the version in its hexadecimal digit).
     * 
     * Returns version as hexadecimal integer (0x1 through 0x8, or 0x0 or 0xF for non-standard).
     * 
     * @return 0x0|0x1|0x2|0x3|0x4|0x5|0x6|0x7|0x8|0xf|0xF UUID version in hexadecimal format
     * 
     * @psalm-return 0x0|0x1|0x2|0x3|0x4|0x5|0x6|0x7|0x8|0xf|0xF
     * @phpstan-return 0x0|0x1|0x2|0x3|0x4|0x5|0x6|0x7|0x8|0xf|0xF
     */
    final public function getVersion(): int
    {
        return hexdec($this->value[14]);
    }

    /**
     * Returns the raw UUID value stored in this object.
     * This is a property getter - use toString() when you need explicit string conversion.
     * 
     * @return string UUID in canonical format (e.g., '123e4567-e89b-12d3-a456-426614174000')
     */
    final public function value(): string
    {
        return $this->value;
    }

    /**
     * Converts UUID to binary representation (16 bytes).
     * Useful for efficient storage in databases as BINARY(16).
     * 
     * @return string 16-byte binary string
     */
    final public function toBinary(): string
    {
        return hex2bin(str_replace('-', '', $this->value));
    }

    /**
     * Validates the UUID string format.
     * Note: This validates an already-created UUID instance, which should always be valid.
     * Use Uuid::fromString($value, validate: true) to validate before creation.
     * 
     * @return bool True if UUID format is valid
     */
    final public function validate(): bool
    {
        return UuidProviderRegistry::getBestProviderForMethod('validate')->validate($this->value);
    }

    /**
     * Returns the UUID string value.
     * Preferred for explicit conversion to string.
     * 
     * @return string UUID in canonical format
     */
    final public function toString(): string
    {
        return $this->value;
    }

    /**
     * Magic method for string conversion.
     * Enables using UUID in string contexts (e.g., echo, concatenation).
     * 
     * @return string UUID in canonical format
     */
    final public function __toString(): string
    {
        return $this->value;
    }
}
