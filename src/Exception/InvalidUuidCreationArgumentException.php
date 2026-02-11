<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Exception;

use InvalidArgumentException;

final class InvalidUuidCreationArgumentException extends \InvalidArgumentException implements UuidException {}
