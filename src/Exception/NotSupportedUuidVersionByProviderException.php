<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Exception;

use LogicException;

final class NotSupportedUuidVersionByProviderException extends \LogicException implements UuidException {}
