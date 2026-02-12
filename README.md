# php-architecture-kit/uuid

Provider-agnostic UUID Value Object for PHP applications, designed as a base for domain-specific Entity IDs in DDD architectures.

## Features

- **Provider-agnostic** - Works with `ramsey/uuid` or `symfony/uid` as backend
- **RFC 9562 compliant** - Full support for UUID versions 1-8, nil, and max
- **Immutable Value Object** - Safe for use in domain models
- **Extensible** - Easy to create domain-specific ID classes (`UserId`, `OrderId`, etc.)
- **Testable** - Accepts `ClockInterface` for deterministic testing
- **Auto-detection** - Automatically selects the best available provider

## Installation

```bash
composer require php-architecture-kit/uuid
```

You also need at least one UUID provider:

```bash
# Option 1: Ramsey UUID (recommended for full RFC 9562 support)
composer require ramsey/uuid

# Option 2: Symfony UID
composer require symfony/uid
```

## Quick Start

```php
use PhpArchitecture\Uuid\Uuid;

// Generate a new UUID (defaults to v7)
$uuid = Uuid::new();

// Generate specific versions
$uuidV4 = Uuid::v4();                    // Random
$uuidV7 = Uuid::v7();                    // Time-ordered (recommended)
$uuidV1 = Uuid::v1();                    // Time-based (legacy)

// Deterministic UUIDs
$namespace = Uuid::fromString(Uuid::NAMESPACE_URL);
$uuidV5 = Uuid::v5($namespace, 'https://example.com');

// Parse existing UUID
$uuid = Uuid::fromString('df516cba-fb13-4f45-8335-00252f1b87e2');
$uuid = Uuid::fromBinary($binaryData);

// Use as Value Object
$uuid->value();       // 'df516cba-fb13-4f45-8335-00252f1b87e2'
$uuid->toString();    // 'df516cba-fb13-4f45-8335-00252f1b87e2'
$uuid->toBinary();    // 16 bytes
$uuid->getVersion();  // 4
$uuid->equals($other); // true/false
```

## Creating Domain-Specific IDs

```php
use PhpArchitecture\Uuid\Uuid;

final class UserId extends Uuid
{
    public static function new(): static
    {
        return static::v7(); // Time-ordered for better DB performance
    }
}

final class OrderId extends Uuid
{
    public static function new(): static
    {
        return static::v4(); // Random for privacy
    }
}

// Usage
$userId = UserId::new();
$orderId = OrderId::fromString('df516cba-fb13-4f45-8335-00252f1b87e2');
```

## Provider Support Matrix

| Feature | Ramsey | Symfony | Notes |
|---------|:------:|:-------:|-------|
| UUID v1 | ✅ | ✅ | Time-based |
| UUID v1 + clockSequence | ✅ | ❌ | Ramsey only |
| UUID v3 | ✅ | ✅ | MD5 hash |
| UUID v4 | ✅ | ✅ | Random |
| UUID v5 | ✅ | ✅ | SHA-1 hash |
| UUID v6 | ✅ | ✅ | Time-ordered (improved v1) |
| UUID v6 + clockSequence | ✅ | ❌ | Ramsey only |
| UUID v7 | ✅ | ✅ | Time-ordered + random |
| UUID v8 | ✅ | ❌ | Custom data |
| Validation | ✅ | ✅ | |

## Performance

All benchmarks run on PHP 8.2, measuring `mode` (most frequent execution time).

### Overhead Analysis

This library adds a thin abstraction layer over the underlying UUID providers. The table below shows the overhead compared to direct provider usage.

#### UUID Parsing (`fromString`)

| Operation | Direct Ramsey | This Library | Overhead |
|-----------|---------------|--------------|----------|
| With validation | 1.23μs | 2.27μs | +1.04μs (+85%) |
| Without validation | N/A | 0.82μs | — |

| Operation | Direct Symfony | This Library | Overhead |
|-----------|----------------|--------------|----------|
| With validation | 2.87μs | 2.68μs | -0.19μs (-7%) |
| Without validation | N/A | 0.85μs | — |

#### UUID Parsing (`fromBinary`)

| Operation | Direct Ramsey | This Library | Overhead |
|-----------|---------------|--------------|----------|
| With validation | 2.44μs | 4.97μs | +2.53μs (+104%) |
| Without validation | N/A | 2.36μs | — |

| Operation | Direct Symfony | This Library | Overhead |
|-----------|----------------|--------------|----------|
| With validation | 3.79μs | 5.20μs | +1.41μs (+37%) |
| Without validation | N/A | 2.33μs | — |

#### UUID Creation

| Version | Direct Ramsey | This Library | Overhead |
|---------|---------------|--------------|----------|
| v1 | 21.58μs | 24.34μs | +2.76μs (+13%) |
| v3 | 8.46μs | 24.90μs | +16.44μs (+194%) |
| v4 | 5.15μs | 6.02μs | +0.87μs (+17%) |
| v5 | 7.67μs | 26.56μs | +18.89μs (+246%) |
| v6 | 22.03μs | 28.14μs | +6.11μs (+28%) |
| v7 | 7.86μs | 9.15μs | +1.29μs (+16%) |
| v8 | 5.07μs | 5.36μs | +0.29μs (+6%) |

| Version | Direct Symfony | This Library | Overhead |
|---------|----------------|--------------|----------|
| v1 | 5.27μs | 11.19μs | +5.92μs (+112%) |
| v3 | 6.17μs | 6.25μs | +0.08μs (+1%) |
| v4 | 1.81μs | 3.04μs | +1.23μs (+68%) |
| v5 | 6.26μs | 9.09μs | +2.83μs (+45%) |
| v6 | 6.93μs | 12.30μs | +5.37μs (+77%) |
| v7 | 3.81μs | 4.65μs | +0.84μs (+22%) |

### Key Insights

1. **Symfony backend is faster** for most operations due to simpler implementation
2. **Parsing without validation** (`fromString($uuid, false)`) is ~3x faster than with validation
3. **v4, v7, v8 have minimal overhead** (~6-17%) - ideal for most use cases
4. **v3, v5 with Ramsey have higher overhead** due to namespace UUID parsing in each call
5. **Autodetect mode** selects the best provider automatically with minimal overhead
6. **For hot paths**, consider:
   - Using `fromString($uuid, false)` when UUID is already validated
   - Caching namespace UUIDs for v3/v5 operations
   - Using v4 or v7 for best performance

### When Overhead Matters

| Use Case | Typical Volume | Impact |
|----------|----------------|--------|
| HTTP Request ID | 1/request | Negligible |
| Entity IDs | 10-100/request | Negligible |
| Batch processing | 10,000+/batch | ~10-60ms extra |
| Real-time systems | μs-sensitive | Consider direct provider |

## API Reference

### Factory Methods

| Method | Description |
|--------|-------------|
| `Uuid::new()` | Creates new UUID (defaults to v7) |
| `Uuid::fromString(string $uuid, bool $validate = true)` | Parse UUID string |
| `Uuid::fromBinary(string $bytes, bool $validate = true)` | Parse 16-byte binary |
| `Uuid::v1(?ClockInterface $clock, ?int $clockSequence, ?string $nodeIdentifier)` | Time-based |
| `Uuid::v3(Uuid $namespace, string $name)` | MD5 hash-based |
| `Uuid::v4()` | Random |
| `Uuid::v5(Uuid $namespace, string $name)` | SHA-1 hash-based |
| `Uuid::v6(?ClockInterface $clock, ?int $clockSequence, ?string $nodeIdentifier)` | Time-ordered |
| `Uuid::v7(?ClockInterface $clock)` | Unix timestamp + random |
| `Uuid::v8(string $customData)` | Custom 16-byte data |
| `Uuid::nil()` | Nil UUID (all zeros) |
| `Uuid::max()` | Max UUID (all ones) |

### Instance Methods

| Method | Description |
|--------|-------------|
| `value(): string` | Get UUID string |
| `toString(): string` | Get UUID string |
| `toBinary(): string` | Get 16-byte binary |
| `getVersion(): int` | Get UUID version (0-15) |
| `getTime(): ?DateTimeImmutable` | Get timestamp (v1/v6/v7) |
| `getClockSequence(): ?int` | Get clock sequence (v1/v6) |
| `getNode(): ?string` | Get node identifier (v1/v6) |
| `equals(self\|string $other): bool` | Compare UUIDs |
| `validate(): bool` | Validate UUID format |

### Namespace Constants

```php
Uuid::NAMESPACE_DNS  // '6ba7b810-9dad-11d1-80b4-00c04fd430c8'
Uuid::NAMESPACE_URL  // '6ba7b811-9dad-11d1-80b4-00c04fd430c8'
Uuid::NAMESPACE_OID  // '6ba7b812-9dad-11d1-80b4-00c04fd430c8'
Uuid::NAMESPACE_X500 // '6ba7b814-9dad-11d1-80b4-00c04fd430c8'
```

## Source of Truth

[RFC 9562 - Universally Unique IDentifiers (UUIDs)](https://www.rfc-editor.org/rfc/rfc9562.html)

## Testing

Package is tested with PHPUnit and PHPBench in the [php-architecture-kit/workspace](https://github.com/php-architecture-kit/workspace) project. 

## License

MIT
