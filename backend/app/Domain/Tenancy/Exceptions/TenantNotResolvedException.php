<?php

declare(strict_types=1);

namespace App\Domain\Tenancy\Exceptions;

use RuntimeException;

/**
 * Thrown instead of silently returning everything: a forgotten tenant context (job, console,
 * new middleware) must fail loudly, never leak another school's rows.
 */
final class TenantNotResolvedException extends RuntimeException
{
    public static function create(): self
    {
        return new self('No school is resolved for this operation.');
    }
}
