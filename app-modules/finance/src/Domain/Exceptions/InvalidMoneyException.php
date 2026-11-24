<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Exceptions;

use DomainException;

final class InvalidMoneyException extends DomainException
{
    public static function negative(int $amount): self
    {
        return new self("An amount of money cannot be negative, got {$amount} ariary.");
    }
}
