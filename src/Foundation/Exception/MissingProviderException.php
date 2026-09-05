<?php

declare(strict_types=1);

namespace PhpArchitecture\Uuid\Foundation\Exception;

use LogicException;

final class MissingProviderException extends LogicException implements UuidException 
{

}
