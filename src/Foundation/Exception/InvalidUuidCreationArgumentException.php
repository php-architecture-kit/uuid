<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Foundation\Exception;

use InvalidArgumentException;

final class InvalidUuidCreationArgumentException extends InvalidArgumentException implements UuidException {}
