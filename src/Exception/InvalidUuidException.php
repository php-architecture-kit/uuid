<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Exception;

use RuntimeException;

final class InvalidUuidException extends \RuntimeException implements UuidException
{
}
