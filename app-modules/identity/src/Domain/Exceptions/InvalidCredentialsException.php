<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Exceptions;

use RuntimeException;

/** One exception for unknown account, wrong password and disabled account: the caller must not learn which. */
final class InvalidCredentialsException extends RuntimeException
{
    public static function create(): self
    {
        return new self('Invalid credentials.');
    }
}
