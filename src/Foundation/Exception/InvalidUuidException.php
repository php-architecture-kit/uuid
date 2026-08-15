<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Foundation\Exception;

use RuntimeException;

final class InvalidUuidException extends RuntimeException implements UuidException
{
}
