<?php

declare(strict_types=1);

namespace Modules\Tenancy\Domain\Exceptions;

use RuntimeException;

final class CrossTenantWriteException extends RuntimeException
{
    public static function create(int $current, int $attempted): self
    {
        return new self("Refused: school {$current} attempted to write a row of school {$attempted}.");
    }
}
